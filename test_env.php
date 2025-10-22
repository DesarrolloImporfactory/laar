<?php
// Test script to verify .env configuration
require_once 'Config/Config.php';

echo "=== Configuración de Variables de Entorno ===\n\n";

echo "Base de Datos Principal:\n";
echo "- HOST: " . HOST . "\n";
echo "- USER: " . USER . "\n";
echo "- PASSWORD: " . (PASSWORD ? str_repeat('*', strlen(PASSWORD)) : 'No configurado') . "\n";
echo "- DB: " . DB . "\n";
echo "- CHARSET: " . CHARSET . "\n\n";

echo "Base de Datos Secundaria:\n";
echo "- HOST2: " . HOST2 . "\n";
echo "- USER2: " . USER2 . "\n";
echo "- PASSWORD2: " . (PASSWORD2 ? str_repeat('*', strlen(PASSWORD2)) : 'No configurado') . "\n";
echo "- DB2: " . DB2 . "\n";
echo "- CHARSET2: " . CHARSET2 . "\n\n";

echo "Configuración de Aplicación:\n";
echo "- APP_ENV: " . APP_ENV . "\n";
echo "- APP_DEBUG: " . (APP_DEBUG ? 'true' : 'false') . "\n\n";

echo "✅ Configuración cargada correctamente desde .env\n";
