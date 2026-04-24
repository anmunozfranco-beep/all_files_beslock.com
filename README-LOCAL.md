# Levantar WordPress localmente con Docker

Estos pasos crean un entorno local con MySQL, WordPress y phpMyAdmin usando `docker compose`.

1) Copia el archivo de variables de ejemplo y revisa credenciales:

```bash
cp .env.example .env
# editar .env según necesites
```

2) Levantar los servicios en segundo plano:

```bash
docker compose up -d
```

3) Accede a:
- WordPress: http://localhost:8000
- phpMyAdmin: http://localhost:8080 (usa las credenciales de `.env`)

Notas:
- El `docker-compose.yml` monta todo el repo (`./`) en `/var/www/html` dentro del contenedor, por lo que el código actual en el repo se servirá en el contenedor.
- Si prefieres montar solo `wp-content`, modifica la sección `volumes` del servicio `wordpress`.
- Para importar la base de datos o ejecutar WP-CLI puedes usar un contenedor adicional con `wp-cli` o ejecutar `docker exec -it beslock_wp bash`.

Comandos útiles:

```bash
# Ver logs
docker compose logs -f

# Parar y eliminar contenedores
docker compose down

# Abrir una shell en el contenedor WordPress
docker exec -it beslock_wp bash
```
