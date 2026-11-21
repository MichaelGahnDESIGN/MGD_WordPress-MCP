<?php

declare(strict_types=1);

namespace Mgd\WordPressMcp\Kit\Install;

// Baut den Client mit cURL. Store je System: JsonFileStore (JTL), CallbackStore (WP-Option, Shopware SystemConfig)
final class Factory
{
    public static function create(Store $store, string $product, string $platform, string $version, string $domain): InstallClient
    {
        return new InstallClient(
            new CurlTransport('MGDInstallClient/' . InstallClient::CLIENT_VERSION . ' (' . $platform . ')'),
            $store,
            $product,
            $platform,
            $version,
            $domain,
        );
    }
}
