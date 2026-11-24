<?php

namespace App\Modules\Facturacion\Infrastructure\Mappers;

use App\Modules\Facturacion\Application\Services\CatalogosFacturacionService;
use InvalidArgumentException;

/**
 * Homologa los datos locales del adquirente a los identificadores de MATIAS.
 * Los IDs de paises/ciudades de GASSTATION nunca se usan como fallback.
 */
class MatiasCustomerMapper
{
    public static function toMatiasCustomer(array $customer): array
    {
        $rawAddress = $customer['address'] ?? $customer['direccion'] ?? null;
        if ($rawAddress !== null && !is_string($rawAddress)) {
            throw new InvalidArgumentException(
                'La dirección del cliente debe ser texto cuando se suministra.'
            );
        }
        $address = trim((string) ($rawAddress ?? ''));

        $countryIso = strtoupper(trim((string) ($customer['country_iso'] ?? '')));
        if ($countryIso === '') {
            throw new InvalidArgumentException(
                'No se puede transmitir el documento: falta el código ISO del país del cliente.'
            );
        }

        // MATIAS documenta explícitamente Colombia como país 45.
        $countryId = $countryIso === 'CO'
            ? '45'
            : app(CatalogosFacturacionService::class)->obtenerCodigoExterno(
                'matias',
                'pais',
                $countryIso
            );

        if ($countryId === null || trim($countryId) === '') {
            throw new InvalidArgumentException(
                "No existe homologación MATIAS para el país ISO {$countryIso}."
            );
        }

        $cityDaneCode = trim((string) ($customer['city_dane_code'] ?? ''));
        if ($countryIso === 'CO') {
            if ($cityDaneCode === '') {
                throw new InvalidArgumentException(
                    'No se puede transmitir el documento: falta el código DANE de la ciudad colombiana.'
                );
            }
            $cityMappingKey = $cityDaneCode;
        } else {
            $localCityId = trim((string) ($customer['city_local_id'] ?? ''));
            if ($localCityId === '') {
                throw new InvalidArgumentException(
                    "No se puede transmitir el documento: falta la referencia local de ciudad para el país {$countryIso}."
                );
            }

            // La referencia local solo se usa como clave interna del catálogo;
            // el ID enviado a MATIAS siempre proviene de una equivalencia activa.
            $cityMappingKey = strtolower("extranjera:{$countryIso}:{$localCityId}");
        }

        // Todas las ciudades se homologan dinámicamente desde mapeos_catalogos_facturacion
        // (proveedor = 'matias', categoria = 'ciudad', codigo_interno = código DANE de 5 dígitos).
        $cityId = app(CatalogosFacturacionService::class)->obtenerCodigoExterno(
            'matias',
            'ciudad',
            $cityMappingKey
        );

        if ($cityId === null || trim($cityId) === '') {
            $cityReference = $countryIso === 'CO' ? "DANE {$cityDaneCode}" : $cityMappingKey;
            throw new InvalidArgumentException(
                "No existe homologación MATIAS para la ciudad {$cityReference}; confirme el valor del catálogo GET /cities."
            );
        }

        $rawIdentityDoc = trim((string) ($customer['identity_document_id'] ?? $customer['tipo_documento_id'] ?? '1'));
        if ($rawIdentityDoc === '') {
            $rawIdentityDoc = '1';
        }

        $mappedIdentityDoc = app(CatalogosFacturacionService::class)->obtenerCodigoExterno(
            'matias',
            'tipo_documento',
            $rawIdentityDoc
        );

        if ($mappedIdentityDoc !== null && trim($mappedIdentityDoc) !== '') {
            $identityDocumentId = trim($mappedIdentityDoc);
        } elseif ($rawIdentityDoc === '1') {
            // Fallback por defecto para Consumidor Final cuando el cliente no define tipo_documento_id.
            $identityDocumentId = '1';
        } else {
            throw new InvalidArgumentException(
                "No existe homologación MATIAS para el tipo de documento '{$rawIdentityDoc}' en el catálogo de facturación electrónica (categoría 'tipo_documento')."
            );
        }

        unset(
            $customer['country_id'],
            $customer['city_id'],
            $customer['country_iso'],
            $customer['city_dane_code'],
            $customer['city_local_id'],
            $customer['direccion'],
            $customer['tipo_documento_id']
        );

        if (trim((string) ($customer['postal_code'] ?? '')) === '') {
            unset($customer['postal_code']);
        }

        $customer['identity_document_id'] = $identityDocumentId;
        $customer['country_id'] = $countryId;
        $customer['city_id'] = $cityId;
        if ($address === '') {
            unset($customer['address']);
        } else {
            $customer['address'] = $address;
        }

        return $customer;
    }
}
