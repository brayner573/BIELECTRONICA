<?php
/**
 * FAXEL BI — Servicio de Facturación Electrónica CPE SUNAT (Perú)
 * Simula la generación de XML UBL 2.1, firma digital y constancia de recepción (CDR)
 */
class FacturacionService
{
    /**
     * Emite un CPE (Factura/Boleta) generando XML UBL 2.1 y CDR
     */
    public static function emitir(array $datos): array
    {
        $rucEmisor    = $datos['emisor_ruc'] ?? '20100000001';
        $razonEmisor  = $datos['emisor_razon'] ?? 'Empresa Demo SAC';
        $tipoDocCpe   = $datos['tipo_cpe'] ?? '01'; // 01 = Factura, 03 = Boleta
        $serie        = $datos['serie'] ?? 'F001';
        $correlativo  = str_pad($datos['correlativo'] ?? '1', 8, '0', STR_PAD_LEFT);
        $numCompleto  = "{$serie}-{$correlativo}";
        
        $rucReceptor  = $datos['cliente_ruc'] ?? '20999999999';
        $razonReceptor= $datos['cliente_razon'] ?? 'Cliente General';
        $tipoDocCli   = $datos['cliente_tipo_doc'] ?? '6'; // 6 = RUC, 1 = DNI
        
        $fechaEmision = $datos['fecha_emision'] ?? date('Y-m-d');
        $moneda       = $datos['moneda'] ?? 'PEN';
        
        $items        = $datos['items'] ?? [];
        
        // Totales
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ($item['cantidad'] * $item['precio_unitario']) / 1.18; // Descontar IGV
        }
        $subtotal = round($subtotal, 2);
        $igv      = round($subtotal * 0.18, 2);
        $total    = round($subtotal + $igv, 2);

        // 1. Generar XML UBL 2.1 real (simplificado pero estructurado)
        $xmlContent = self::construirXMLUBL21([
            'ruc_emisor'    => $rucEmisor,
            'razon_emisor'  => $razonEmisor,
            'tipo_cpe'      => $tipoDocCpe,
            'numero'        => $numCompleto,
            'fecha'         => $fechaEmision,
            'ruc_receptor'  => $rucReceptor,
            'razon_receptor'=> $razonReceptor,
            'tipo_doc_cli'  => $tipoDocCli,
            'moneda'        => $moneda,
            'subtotal'      => $subtotal,
            'igv'           => $igv,
            'total'         => $total,
            'items'         => $items
        ]);

        // 2. Simular firma digital y generar hash CPE
        $hashCPE = hash('sha256', $xmlContent . time());
        $xmlSignedContent = str_replace('<!--DS_SIGNATURE_PLACEHOLDER-->', "<ds:Signature><ds:SignatureValue>{$hashCPE}</ds:SignatureValue></ds:Signature>", $xmlContent);

        // 3. Crear directorio y guardar XML
        $xmlDir = dirname(dirname(__DIR__)) . '/public/uploads/xml/';
        if (!is_dir($xmlDir)) {
            mkdir($xmlDir, 0755, true);
        }
        $xmlFileName = "{$rucEmisor}-{$tipoDocCpe}-{$numCompleto}.xml";
        $xmlPath = '/uploads/xml/' . $xmlFileName;
        file_put_contents($xmlDir . $xmlFileName, $xmlSignedContent);

        // 4. Simular generación de CDR de respuesta de SUNAT (Constancia de Recepción)
        $cdrContent = self::construirCDR([
            'ruc_emisor'   => $rucEmisor,
            'tipo_cpe'     => $tipoDocCpe,
            'numero'       => $numCompleto,
            'hash_cpe'     => $hashCPE,
            'fecha_recep'  => date('Y-m-d H:i:s')
        ]);
        
        $cdrDir = dirname(dirname(__DIR__)) . '/public/uploads/cdr/';
        if (!is_dir($cdrDir)) {
            mkdir($cdrDir, 0755, true);
        }
        $cdrFileName = "R-{$rucEmisor}-{$tipoDocCpe}-{$numCompleto}.xml";
        $cdrPath = '/uploads/cdr/' . $cdrFileName;
        file_put_contents($cdrDir . $cdrFileName, $cdrContent);

        return [
            'success'         => true,
            'xml_path'        => $xmlPath,
            'cdr_path'        => $cdrPath,
            'hash_cpe'        => $hashCPE,
            'numero_completo' => $numCompleto,
            'subtotal'        => $subtotal,
            'igv'             => $igv,
            'total'           => $total
        ];
    }

    /**
     * Construye un XML UBL 2.1 estructurado
     */
    private static function construirXMLUBL21(array $data): string
    {
        $itemsXml = '';
        foreach ($data['items'] as $index => $item) {
            $num = $index + 1;
            $cant = $item['cantidad'];
            $preUnit = $item['precio_unitario'];
            $lineSub = round(($cant * $preUnit) / 1.18, 2);
            $lineIgv = round($lineSub * 0.18, 2);
            $lineTotal = round($lineSub + $lineIgv, 2);
            
            $itemsXml .= "
    <cac:InvoiceLine>
        <cbc:ID>{$num}</cbc:ID>
        <cbc:InvoicedQuantity unitCode=\"NIU\">{$cant}</cbc:InvoicedQuantity>
        <cbc:LineExtensionAmount currencyID=\"{$data['moneda']}\">{$lineSub}</cbc:LineExtensionAmount>
        <cac:PricingReference>
            <cac:AlternativeConditionPrice>
                <cbc:PriceAmount currencyID=\"{$data['moneda']}\">{$preUnit}</cbc:PriceAmount>
                <cbc:PriceTypeCode>01</cbc:PriceTypeCode>
            </cac:AlternativeConditionPrice>
        </cac:PricingReference>
        <cac:TaxTotal>
            <cbc:TaxAmount currencyID=\"{$data['moneda']}\">{$lineIgv}</cbc:TaxAmount>
            <cac:TaxSubtotal>
                <cbc:TaxableAmount currencyID=\"{$data['moneda']}\">{$lineSub}</cbc:TaxableAmount>
                <cbc:TaxAmount currencyID=\"{$data['moneda']}\">{$lineIgv}</cbc:TaxAmount>
                <cac:TaxScheme>
                    <cbc:ID>1000</cbc:ID>
                    <cbc:Name>IGV</cbc:Name>
                    <cbc:TaxTypeCode>VAT</cbc:TaxTypeCode>
                </cac:TaxScheme>
            </cac:TaxSubtotal>
        </cac:TaxTotal>
        <cac:Item>
            <cbc:Description>" . htmlspecialchars($item['nombre']) . "</cbc:Description>
            <cac:SellersItemIdentification>
                <cbc:ID>" . htmlspecialchars($item['codigo']) . "</cbc:ID>
            </cac:SellersItemIdentification>
        </cac:Item>
        <cac:Price>
            <cbc:PriceAmount currencyID=\"{$data['moneda']}\">" . round($preUnit / 1.18, 4) . "</cbc:PriceAmount>
        </cac:Price>
    </cac:InvoiceLine>";
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Invoice xmlns=\"urn:oasis:names:specification:ubl:schema:xsd:Invoice-2\"
         xmlns:cac=\"urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2\"
         xmlns:cbc=\"urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2\"
         xmlns:ds=\"http://www.w3.org/2000/09/xmldsig#\">
    <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
    <cbc:CustomizationID>2.0</cbc:CustomizationID>
    <cbc:ID>{$data['numero']}</cbc:ID>
    <cbc:IssueDate>{$data['fecha']}</cbc:IssueDate>
    <cbc:InvoiceTypeCode>{$data['tipo_cpe']}</cbc:InvoiceTypeCode>
    <cbc:DocumentCurrencyCode>{$data['moneda']}</cbc:DocumentCurrencyCode>
    
    <!--DS_SIGNATURE_PLACEHOLDER-->

    <cac:AccountingSupplierParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"6\">{$data['ruc_emisor']}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>" . htmlspecialchars($data['razon_emisor']) . "</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingSupplierParty>
    
    <cac:AccountingCustomerParty>
        <cac:Party>
            <cac:PartyIdentification>
                <cbc:ID schemeID=\"{$data['tipo_doc_cli']}\">{$data['ruc_receptor']}</cbc:ID>
            </cac:PartyIdentification>
            <cac:PartyLegalEntity>
                <cbc:RegistrationName>" . htmlspecialchars($data['razon_receptor']) . "</cbc:RegistrationName>
            </cac:PartyLegalEntity>
        </cac:Party>
    </cac:AccountingCustomerParty>

    <cac:TaxTotal>
        <cbc:TaxAmount currencyID=\"{$data['moneda']}\">{$data['igv']}</cbc:TaxAmount>
        <cac:TaxSubtotal>
            <cbc:TaxableAmount currencyID=\"{$data['moneda']}\">{$data['subtotal']}</cbc:TaxableAmount>
            <cbc:TaxAmount currencyID=\"{$data['moneda']}\">{$data['igv']}</cbc:TaxAmount>
            <cac:TaxScheme>
                <cbc:ID>1000</cbc:ID>
                <cbc:Name>IGV</cbc:Name>
                <cbc:TaxTypeCode>VAT</cbc:TaxScheme>
            </cac:TaxSubtotal>
    </cac:TaxTotal>

    <cac:LegalMonetaryTotal>
        <cbc:LineExtensionAmount currencyID=\"{$data['moneda']}\">{$data['subtotal']}</cbc:LineExtensionAmount>
        <cbc:TaxInclusiveAmount currencyID=\"{$data['moneda']}\">{$data['total']}</cbc:TaxInclusiveAmount>
        <cbc:PayableAmount currencyID=\"{$data['moneda']}\">{$data['total']}</cbc:PayableAmount>
    </cac:LegalMonetaryTotal>
    
    {$itemsXml}
</Invoice>";
    }

    /**
     * Construye un CDR (Constancia de Recepción de SUNAT) xml
     */
    private static function construirCDR(array $data): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<ApplicationResponse xmlns=\"urn:oasis:names:specification:ubl:schema:xsd:ApplicationResponse-2\"
                     xmlns:cac=\"urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2\"
                     xmlns:cbc=\"urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2\">
    <cbc:ID>CDR-{$data['numero']}</cbc:ID>
    <cbc:IssueDate>" . date('Y-m-d') . "</cbc:IssueDate>
    <cbc:ResponseDate>{$data['fecha_recep']}</cbc:ResponseDate>
    <cac:SenderParty>
        <cac:PartyIdentification>
            <cbc:ID>SUNAT</cbc:ID>
        </cac:PartyIdentification>
    </cac:SenderParty>
    <cac:ReceiverParty>
        <cac:PartyIdentification>
            <cbc:ID schemeID=\"6\">{$data['ruc_emisor']}</cbc:ID>
        </cac:PartyIdentification>
    </cac:ReceiverParty>
    <cac:DocumentResponse>
        <cac:Response>
            <cbc:ReferenceID>{$data['numero']}</cbc:ReferenceID>
            <cbc:ResponseCode>0</cbc:ResponseCode>
            <cbc:Description>La Factura numero {$data['numero']} ha sido aceptada.</cbc:Description>
        </cac:Response>
        <cac:DocumentReference>
            <cbc:ID>{$data['numero']}</cbc:ID>
            <cbc:UUID>{$data['hash_cpe']}</cbc:UUID>
        </cac:DocumentReference>
    </cac:DocumentResponse>
</ApplicationResponse>";
    }
}
