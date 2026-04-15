<?php

declare(strict_types=1);

namespace App\Services\Parsers;

use RuntimeException;
use SimpleXMLElement;

final class XmlTransactionParser extends AbstractTransactionParser
{
    public function parse(string $path): array
    {
        $xml = simplexml_load_string(file_get_contents($path));

        if (!$xml instanceof SimpleXMLElement) {
            throw new RuntimeException('Invalid XML format.');
        }

        $rows = [];

        foreach ($xml->transaction as $transaction) {
            $rows[] = $this->normalizeRecord([
                'transaction_id' => $this->readXmlField($transaction, 'transaction_id'),
                'account_number' => $this->readXmlField($transaction, 'account_number'),
                'transaction_date' => $this->readXmlField($transaction, 'transaction_date'),
                'amount' => $this->readXmlField($transaction, 'amount'),
                'currency' => $this->readXmlField($transaction, 'currency'),
            ]);
        }

        return $rows;
    }

    private function readXmlField(SimpleXMLElement $transaction, string $field): ?string
    {
        if (!isset($transaction->{$field})) {
            return null;
        }

        return $this->normalizeString($transaction->{$field}->__toString());
    }
}
