#!/usr/bin/env php
<?php

/**
 * Gerador de chave de criptografia AES-256-GCM para QueryBase
 *
 * Esta chave deve ser compartilhada entre querybase-web (Laravel) e querybase-api (Golang)
 * para permitir criptografia/descriptografia de senhas de datasources.
 */

echo "==========================================================\n";
echo "  QueryBase - Gerador de Chave de Criptografia\n";
echo "==========================================================\n\n";

// Gera 32 bytes aleatórios (256 bits para AES-256)
$key = random_bytes(32);
$keyBase64 = base64_encode($key);

echo "Chave gerada com sucesso!\n\n";
echo "Adicione a linha abaixo nos arquivos .env de AMBOS os projetos:\n";
echo "  - querybase-web/.env\n";
echo "  - querybase-api/.env\n\n";
echo "┌─────────────────────────────────────────────────────────┐\n";
echo "│ QUERYBASE_ENCRYPTION_KEY={$keyBase64}\n";
echo "└─────────────────────────────────────────────────────────┘\n\n";

echo "IMPORTANTE:\n";
echo "  • Esta chave DEVE ser a mesma nos dois projetos\n";
echo "  • Mantenha esta chave em SEGREDO (não commite no git)\n";
echo "  • Se perder a chave, senhas criptografadas ficam inacessíveis\n";
echo "  • Use .env.example para documentar, mas sem a chave real\n\n";
