# Configuración de Variables de Entorno

Este proyecto ahora utiliza variables de entorno para manejar la configuración de manera segura y flexible.

## Configuración Inicial

1. Copia el archivo `.env.example` y renómbralo como `.env`:

   ```bash
   cp .env.example .env
   ```

2. Edita el archivo `.env` con tus configuraciones reales:
   ```bash
   nano .env
   ```

## Variables Disponibles

### Base de Datos Principal

- `DB_HOST`: Host de la base de datos principal
- `DB_USER`: Usuario de la base de datos principal
- `DB_PASSWORD`: Contraseña de la base de datos principal
- `DB_NAME`: Nombre de la base de datos principal
- `DB_CHARSET`: Charset de la base de datos principal (por defecto: utf8)

### Base de Datos Secundaria

- `DB_HOST2`: Host de la base de datos secundaria
- `DB_USER2`: Usuario de la base de datos secundaria
- `DB_PASSWORD2`: Contraseña de la base de datos secundaria
- `DB_NAME2`: Nombre de la base de datos secundaria
- `DB_CHARSET2`: Charset de la base de datos secundaria (por defecto: utf8)

### Configuración de la Aplicación

- `APP_ENV`: Entorno de la aplicación (development, production)
- `APP_DEBUG`: Modo debug (true/false)

## Uso en el Código

Para acceder a las variables de entorno en tu código PHP, puedes usar la función `env()`:

```php
$host = env('DB_HOST', 'localhost'); // Con valor por defecto
$debug = env('APP_DEBUG', false);
```

## Seguridad

- **NUNCA** subas el archivo `.env` al repositorio
- El archivo `.env` está incluido en `.gitignore`
- Siempre mantén actualizado el archivo `.env.example` con las variables necesarias (sin valores sensibles)
- Usa valores por defecto seguros en la función `env()`

## Migración

Si ya tenías configuraciones hardcodeadas, han sido migradas automáticamente al nuevo sistema. Las constantes originales (`HOST`, `USER`, `PASSWORD`, etc.) siguen funcionando para mantener la compatibilidad.
