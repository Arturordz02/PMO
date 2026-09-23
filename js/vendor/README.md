# PMO Solutions — Dependencias Críticas de Frontend (Vendor)

Este directorio contiene copias locales minificadas de librerías JavaScript de terceros utilizadas por la aplicación, alojadas localmente para eliminar la dependencia de redes externas y garantizar máxima disponibilidad, seguridad (CSP estricto) y rendimiento.

---

## 1. Chart.js (v4.4.0)
* **Archivo**: `chart.umd.min.js`
* **Versión**: 4.4.0 (UMD build)
* **Origen**: `https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js`
* **Licencia**: MIT License (ver `LICENSE-chartjs.txt`)
* **Uso**: Renderizado de gráficos de radar interactivos para la evaluación de habilidades blandas (`js/modules/soft-skills.js`).
* **SHA-256**: `0e2326c6868072bec1592760c6729043caeea2960a2b46cee6a2192aac6abff0`

---

## 2. jsPDF (v2.5.1)
* **Archivo**: `jspdf.umd.min.js`
* **Versión**: 2.5.1 (UMD build)
* **Origen**: `https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js`
* **Licencia**: MIT License (ver `LICENSE-jspdf.txt`)
* **Uso**: Generación en el cliente de reportes ejecutivos en formato PDF para la evaluación de habilidades blandas (`js/modules/soft-skills.js`).
* **SHA-256**: `98ccf17aa10c20bb1301762618fcc9b6ab3a4e7f26b6071d64d0b41154df3875`