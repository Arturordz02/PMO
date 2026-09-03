# ==============================================================================
# PMO SOLUTIONS - SCRIPT DE EMPAQUETADO PARA PRODUCCION (GODADDY / CPANEL)
# ==============================================================================

$sourceDir = $PSScriptRoot
$releaseDir = Join-Path $sourceDir "release"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$zipFile = Join-Path $releaseDir "pmo-solutions-production_$timestamp.zip"
$tempFolder = Join-Path $releaseDir "pmo_temp"

Write-Host "======================================================================" -ForegroundColor Cyan
Write-Host " INICIANDO EMPAQUETADO DE PRODUCCION - PMO SOLUTIONS (GODADDY READY)" -ForegroundColor Cyan
Write-Host "======================================================================" -ForegroundColor Cyan

if (Test-Path $releaseDir) {
    Remove-Item $releaseDir -Recurse -Force
}
New-Item -ItemType Directory -Path $tempFolder -Force | Out-Null

$itemsToCopy = @(
    "app",
    "css",
    "js",
    "img",
    "backend",
    "storage",
    "index.php",
    ".htaccess",
    "robots.txt",
    "sitemap.xml",
    "README.md"
)

Write-Host "Copiando componentes optimizados de produccion..." -ForegroundColor Yellow
foreach ($item in $itemsToCopy) {
    $srcPath = Join-Path $sourceDir $item
    if (Test-Path $srcPath) {
        Copy-Item -Path $srcPath -Destination $tempFolder -Recurse -Force
        Write-Host "  [+] $item" -ForegroundColor Green
    }
}

# Limpiar archivos de logs para que suba limpio
$logsFolder = Join-Path $tempFolder "storage\logs"
if (Test-Path $logsFolder) {
    Get-ChildItem -Path $logsFolder -Filter "*.log" | Remove-Item -Force
}

Write-Host "`nGenerando archivo ZIP de distribucion..." -ForegroundColor Yellow
Compress-Archive -Path "$tempFolder\*" -DestinationPath $zipFile -CompressionLevel Optimal

# Eliminar carpeta temporal
Remove-Item -Path $tempFolder -Recurse -Force

$fileInfo = Get-Item $zipFile
$sizeMB = [math]::Round($fileInfo.Length / 1MB, 2)

Write-Host "`n======================================================================" -ForegroundColor Green
Write-Host " PAQUETE DE PRODUCCION GENERADO CON EXITO!" -ForegroundColor Green
Write-Host "======================================================================" -ForegroundColor Green
Write-Host " Archivo generado : $zipFile" -ForegroundColor White
Write-Host " Peso comprimido  : $sizeMB MB" -ForegroundColor White
Write-Host "`n Instrucciones para GoDaddy:" -ForegroundColor Cyan
Write-Host " 1. Entra al cPanel de GoDaddy -> Administrador de Archivos." -ForegroundColor White
Write-Host " 2. Abre la carpeta 'public_html'." -ForegroundColor White
Write-Host " 3. Sube este archivo ZIP y dale clic en 'Extraer'." -ForegroundColor White
Write-Host "======================================================================`n" -ForegroundColor Green
