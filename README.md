# Edison
Open-source technical dashboard: the Swiss electricity mix and solar conditions, reconstructed in real time using public data (Energy-Charts / Fraunhofer ISE, Open-Meteo). No database, no API key.  
Online: https://edison.vektoriel.com  
Code: https://github.com/toninodigiacomo/edison  
Licence: (GNU GPL v3.0)[https://github.com/toninodigiacomo/edison/blob/c3f63b1225cc023d5770c7bbd8114a48fefc62b6/LICENSE.md]

## Structure
```txt
edison/                  # mounted in the container at /var/www/html
  ├── index.html         # the dashboard (a single page, HTML/CSS/JS)
  ├── api/
  │   └── energy.php     # server-side proxy to Energy-Charts
  └── assets/
      └── edison-icon.png
```

## Why use a PHP proxy for production data
**The site uses two public APIs:**  
- **Open-Meteo** (weather, solar radiation) called directly from the browser, with no issues: reliable CORS, no key required.
- **Energy-Charts** (Swiss energy mix) called server-side via api/energy.php, not directly from the browser.  
Tests have shown inconsistent responses (403/404) depending on the origin of the request; the server-to-server call is reliable.  
  
```energy.php``` queries https://api.energy-charts.info/v2/public_power?country=ch using a 7-day rolling start/end window, rounded to the nearest full hour (the API rejects non-aligned timestamps); caches the result for 15 minutes in a temporary file, so as not to query the API every time the page is loaded; serves a slightly out-of-date response rather than an error if the upstream server is temporarily unavailable.

## Time lag in Swiss data
Unlike Germany (which provides near real-time data), Swissgrid does not publish official real-time flow data.  
Swiss data via Energy-Charts is approximately 24 hours behind.  
This is normal; it is not a bug — the dashboard explicitly states this (under the ‘Generation mix’ section and the ‘Latest reading’ KPI).

## Deploiment
```bash
cd edison/docker
docker compose up -d
```
⚠️​ Update the docker file as per your need.  

**Image** ```php:8.2-apache``` no custom Dockerfile.  
**Container name** edison, port hôte 8218:80 (debug direct access ```http://YOUR.PRIVATE.IP:8218```).
  
> [!NOTE]
> All the rendering (graphs, weather maps) is done using plain JavaScript and Chart.js (CDN), with no build process required: simply edit src/index.html, and a page reload is all that’s needed.  
> The two banners (top: logo/status; bottom: GitHub link/copyright) are fixed in place using CSS (position: fixed), with corresponding padding applied to the body element so as not to obscure the content.

---

## License
**GNU GPL v3.0** [LICENCE.md](https://github.com/toninodigiacomo/codex/blob/ced88567378bde78b3d0e61ee955449f240510e1/LICENSE.md)
