# PMO Solutions - Versión Producción GoDaddy (cPanel)

Esta versión del proyecto está optimizada y organizada para su despliegue seguro en entornos de hosting compartido cPanel / GoDaddy.

---

## 📁 Estructura del Proyecto

```text
PMO-Produccion-GoDaddy/
├── database_setup/             # Scripts SQL y migraciones para la base de datos
│   ├── schema.sql
│   ├── schema_v2.sql
│   └── migrations/
├── private_app/                # Núcleo MVC y lógica privada (fuera de la raíz web)
│   ├── app/                    # Controladores, Modelos, Vistas y Core
│   ├── backend/                # Scripts de soporte y utilitarios
│   ├── config/                 # Configuración del sistema
│   ├── modules/                # Módulos del sistema (ej. chatbot)
│   ├── storage/                # Almacenamiento seguro, logs y cache
│   ├── .env.example
│   ├── .htaccess
│   └── env.example.php
└── public_html/                # Raíz pública web (accesible vía HTTP)
    ├── css/
    ├── img/
    ├── js/
    ├── modules/
    ├── .htaccess
    ├── index.php               # Front controller que conecta con private_app
    ├── robots.txt
    └── sitemap.xml
```

---

## 🚀 Despliegue en GoDaddy (cPanel)

1. **Subida de Archivos:**
   - Colocar el contenido de `public_html/` en el directorio web público (por defecto `public_html` del cPanel).
   - Colocar la carpeta `private_app/` al mismo nivel que `public_html` (fuera del alcance web directo para máxima seguridad).
2. **Configuración de Variables de Entorno:**
   - En `private_app/`, renombrar `env.example.php` a `env.php` (o configurar `.env`) con las credenciales de la base de datos de producción y configuración SMTP.
3. **Base de Datos:**
   - Importar `database_setup/schema.sql` (y las migraciones correspondientes) en la base de datos MySQL de cPanel.
