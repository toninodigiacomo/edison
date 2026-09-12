# edison
A 100% static site (HTML/CSS/JS, no PHP code executed) served by php:8.2-apache to ensure it runs on the same platform as my other sites.

## Structure
'''txt
courant/
├── compose.yml
├── README.md
└── src/
    └── index.html   # the complete dashboard (a single page)
'''

### 1. Shared Docker network with Nginx Proxy Manager

compose.yml assumes an external network named npm_proxy. Check using:

´´´bash
docker network ls
docker inspect <container_codex_ou_grimoire> --format '{{json .NetworkSettings.Networks}}'
´´´

… and adjust the ´´´name:´´´ value in ´´´compose.yml´´´ accordingly.

### 2. Start the service
bash´´´bash
cd courant
docker compose up -d
´´´

A standard container does not expose any ports on the host: it can only be accessed via the shared Docker network.

### 3. Configure the Proxy Host in Nginx Proxy Manager
Domain Names: edison.vektoriel.com
Scheme: http
Forward Hostname / IP: courant (the service name = internal DNS name on the Docker network)
Forward Port: 80
SSL: enable, request a Let’s Encrypt certificate, force HTTPS

### 4. DNS
Add a CNAME or A record for courant.vektoriel.com pointing to your public IP address or dynamic domain, as with the other subdomains already described.

> Notes !
> No environment variables, no database: the site simply makes browser-side fetch() calls to Open-Meteo and Energy-Charts.
> No build process: the contents of `src/` are mounted as-is in `/var/www/html`. Any changes to `index.html` take effect immediately (simply reloading the page is sufficient; there is no need to restart the container).
