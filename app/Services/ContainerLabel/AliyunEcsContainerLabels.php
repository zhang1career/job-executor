<?php

namespace App\Services\ContainerLabel;

use Exception;
use Illuminate\Support\Facades\Log;

/**
 * ECS instances in a VPC IPv4 CIDR. Each row has name and appmap (ECS tag "appmap") for DiscoverService.
 */
trait AliyunEcsContainerLabels
{
    /**
     * True when ALIYUN_VPC_CIDR and ECS API credentials (access key, region) are set.
     */
    protected function isAliyunEnvironment(): bool
    {
        $cidr = config('aliyun.vpc_cidr');
        if ($cidr === null || $cidr === '') {
            return false;
        }

        return ! empty(config('aliyun.access_key_id'))
            && ! empty(config('aliyun.access_key_secret'))
            && ! empty(config('aliyun.region_id'));
    }

    /**
     * List ECS instances whose primary VPC private IPv4 lies in config aliyun.vpc_cidr and tag appmap is set.
     *
     * Configuration (.env → config/aliyun.php): ALIYUN_VPC_CIDR, ALIYUN_ACCESS_KEY_ID/SECRET, ALIYUN_REGION_ID.
     * Optional: ALIYUN_VPC_ID narrows DescribeInstances when set.
     *
     * @return array<int, array{name: string, appmap: string}>
     */
    protected function getAliyunEcsMachinesInVpcCidr(): array
    {
        $vpcCidr = (string) config('aliyun.vpc_cidr');
        $vpcId = config('aliyun.vpc_id');
        $accessKeyId = config('aliyun.access_key_id');
        $accessKeySecret = config('aliyun.access_key_secret');
        $regionId = config('aliyun.region_id');

        if (empty($accessKeyId) || empty($accessKeySecret) || empty($regionId)) {
            Log::error('Aliyun ECS: missing ALIYUN_ACCESS_KEY_ID, ALIYUN_ACCESS_KEY_SECRET, or ALIYUN_REGION_ID');

            return [];
        }

        if (! $this->isValidIpv4Cidr($vpcCidr)) {
            Log::error('Aliyun ECS: invalid VPC CIDR', ['cidr' => $vpcCidr]);

            return [];
        }

        try {
            $instances = $this->aliyunDescribeAllInstances($regionId, $accessKeyId, $accessKeySecret, $vpcId ?: null);
        } catch (Exception $e) {
            Log::error('Aliyun ECS DescribeInstances failed', ['error' => $e->getMessage()]);

            return [];
        }

        $out = [];
        foreach ($instances as $inst) {
            $privateIp = $this->aliyunPrimaryVpcPrivateIp($inst);
            if ($privateIp === null || ! $this->ipv4InCidr($privateIp, $vpcCidr)) {
                continue;
            }

            $appmap = $this->aliyunTagValue($inst, 'appmap');
            if ($appmap === null || $appmap === '') {
                continue;
            }

            $name = (string) ($inst['InstanceName'] ?? '');
            if ($name === '') {
                $name = (string) ($inst['HostName'] ?? '');
            }
            if ($name === '') {
                $name = (string) ($inst['InstanceId'] ?? '');
            }

            $out[] = [
                'name' => $name,
                'appmap' => $appmap,
            ];
        }

        return $out;
    }

    private function isValidIpv4Cidr(string $cidr): bool
    {
        $parts = explode('/', $cidr, 2);
        if (count($parts) !== 2) {
            return false;
        }
        [$ip, $bits] = $parts;
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $bits = (int) $bits;

        return $bits >= 0 && $bits <= 32;
    }

    private function ipv4InCidr(string $ip, string $cidr): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        [$subnet, $bits] = array_pad(explode('/', $cidr, 2), 2, '32');
        $bits = (int) $bits;
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        if ($bits === 0) {
            return true;
        }
        $mask = (~0 << (32 - $bits)) & 0xFFFFFFFF;

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }

    private function aliyunPrimaryVpcPrivateIp(array $inst): ?string
    {
        $vpc = $inst['VpcAttributes'] ?? [];
        $ips = $vpc['PrivateIpAddress'] ?? [];
        if (isset($ips['IpAddress']) && is_array($ips['IpAddress']) && $ips['IpAddress'] !== []) {
            $first = $ips['IpAddress'][0] ?? null;
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return null;
    }

    private function aliyunTagValue(array $inst, string $key): ?string
    {
        $tags = $inst['Tags'] ?? [];
        $tagList = $tags['Tag'] ?? [];
        if (! is_array($tagList)) {
            return null;
        }
        foreach ($tagList as $tag) {
            if (! is_array($tag)) {
                continue;
            }
            if (($tag['TagKey'] ?? '') === $key) {
                return (string) ($tag['TagValue'] ?? '');
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function aliyunDescribeAllInstances(string $regionId, string $accessKeyId, string $accessKeySecret, ?string $vpcId): array
    {
        $endpoint = sprintf('https://ecs.%s.aliyuncs.com', $regionId);
        $all = [];
        $page = 1;
        $pageSize = 100;

        do {
            $params = [
                'Action' => 'DescribeInstances',
                'Version' => '2014-05-26',
                'Format' => 'JSON',
                'AccessKeyId' => $accessKeyId,
                'SignatureMethod' => 'HMAC-SHA1',
                'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
                'SignatureVersion' => '1.0',
                'SignatureNonce' => $this->aliyunSignatureNonce(),
                'RegionId' => $regionId,
                'PageNumber' => (string) $page,
                'PageSize' => (string) $pageSize,
            ];
            if ($vpcId) {
                $params['VpcId'] = $vpcId;
            }

            $params['Signature'] = $this->aliyunRpcSignature($params, $accessKeySecret);
            $url = $endpoint.'?'.$this->aliyunBuildQuery($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body === false || $code !== 200) {
                throw new Exception("DescribeInstances HTTP $code");
            }

            $data = json_decode($body, true);
            if (! is_array($data)) {
                throw new Exception('DescribeInstances invalid JSON');
            }
            if (isset($data['Code'])) {
                throw new Exception((string) ($data['Message'] ?? $data['Code']));
            }

            $instances = $data['Instances']['Instance'] ?? [];
            if (! is_array($instances)) {
                $instances = [];
            }
            // Single instance returns object shape in some clients; normalize to list
            if ($instances !== [] && $this->aliyunIsAssocInstanceList($instances)) {
                $instances = [$instances];
            }

            foreach ($instances as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }

            $total = (int) ($data['TotalCount'] ?? 0);
            $page++;
            $hasMore = ($page - 1) * $pageSize < $total;
        } while ($hasMore);

        return $all;
    }

    private function aliyunIsAssocInstanceList(array $instances): bool
    {
        return array_key_exists('InstanceId', $instances);
    }

    private function aliyunSignatureNonce(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception) {
            return uniqid('nonce_', true);
        }
    }

    /**
     * @param  array<string, string>  $params
     */
    private function aliyunRpcSignature(array $params, string $accessKeySecret): string
    {
        ksort($params);
        $canonical = '';
        foreach ($params as $k => $v) {
            $canonical .= '&'.$this->aliyunPercentEncode($k).'='.$this->aliyunPercentEncode($v);
        }
        $canonical = substr($canonical, 1);
        $stringToSign = 'GET&'.$this->aliyunPercentEncode('/').'&'.$this->aliyunPercentEncode($canonical);

        return base64_encode(hash_hmac('sha1', $stringToSign, $accessKeySecret.'&', true));
    }

    /**
     * @param  array<string, string>  $params
     */
    private function aliyunBuildQuery(array $params): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $k => $v) {
            $pairs[] = $this->aliyunPercentEncode($k).'='.$this->aliyunPercentEncode($v);
        }

        return implode('&', $pairs);
    }

    private function aliyunPercentEncode(string $s): string
    {
        $res = rawurlencode($s);
        $res = str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], $res);

        return $res;
    }
}
