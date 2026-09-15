# Proyecto: Ikaan Villa Spa — Landing de Bodas

Contexto para cualquier agente de IA que trabaje en este repositorio. Léelo completo antes
de tocar código: este proyecto **no** es una app de Next.js y aplicarle los patrones
habituales de React lo rompe.

---

## 1. Qué es

Landing page de una sola pantalla para **Ikaan Villa Spa**, un venue de bodas y eventos en
Montemorelos, Nuevo León. El sitio vende el lugar como sede de bodas: espacios, paquetes,
video-testimonios, galería y un formulario de contacto.

- **Repo:** `desarrolloweb-hub/ikaanWeb`
- **Publicado en:** GitHub Pages — `https://desarrolloweb-hub.github.io/ikaanWeb/`
- **Idioma del sitio y de los commits:** español

---

## 2. Stack — importante

Esto es un **export de Claude Design**, no un proyecto con framework. No hay `package.json`,
no hay build, no hay `node_modules`, no hay servidor.

| | |
|---|---|
| **Archivo único** | `index.html` — todo el sitio vive aquí (~47 KB) |
| **Runtime** | `support.js` — intérprete del formato `<x-dc>` (no editar) |
| **Imágenes** | `image-slot.js` — el custom element `<image-slot>` (no editar) |
| **Estilos** | Inline en cada elemento, más un `<style>` dentro de `<helmet>` |
| **Lógica** | Una clase `Component extends DCLogic` al final del archivo |
| **Fuentes** | Cormorant Garamond y Lora, desde Google Fonts |

### Cómo correrlo

No hay `npm run dev`. Es estático:

```bash
python3 -m http.server 8080 --bind 127.0.0.1
# http://localhost:8080
```

Los videos y las imágenes se cachean fuerte: usa **Cmd+Shift+R** al verificar cambios.

---

## 3. El formato `<x-dc>` — lo que hay que entender

El HTML no es HTML normal. `support.js` lo interpreta en el navegador y lo monta como React.

### Plantillas `{{ }}`

```html
<a href="{{ whatsappHref }}">WhatsApp</a>
<video autoPlay="{{ true }}" muted="{{ true }}">
```

Los valores salen de `renderVals()` en la clase `Component`, hasta abajo del archivo. Si
agregas un `{{ algo }}` en el markup, tiene que existir una clave `algo` en `renderVals()`.

### Condicionales

```html
<sc-if value="{{ submitted }}" hint-placeholder-val="{{ false }}"> ... </sc-if>
```

### Hover y focus

No hay `:hover` en CSS para estos elementos. Se usa un atributo:

```html
<a style="color:#333" style-hover="color:#7d5411">
```

### `<image-slot>`

Custom element que rellena su contenedor con `object-fit: cover`:

```html
<image-slot id="space-1" shape="rect" src="assets/foto.webp"
            alt="Descripción real" placeholder="Texto si no hay foto"
            style="width:100%;aspect-ratio:16/9;display:block"></image-slot>
```

- **Sin `src`** muestra un recuadro gris punteado con el texto del `placeholder`. Eso no es
  un error: es un hueco esperando foto.
- **Necesita altura explícita.** Recorta al centro para llenar el marco, así que una foto
  vertical en un marco ancho pierde la cabeza de la gente. Ver §6.

---

## 4. Estructura de `index.html`

En orden de aparición:

| Sección | `id` | Contenido |
|---|---|---|
| Nav | — | Menú flotante, colapsa a hamburguesa |
| Hero | — | Video `hero-bodas.mp4`, rótulo "Jardines para boda", CTAs "Agendar un recorrido" y "WhatsApp" |
| Contacto | `#contacto` | Texto a la izquierda + formulario. Tarjeta con degradado animado |
| Espacios | `#espacios` | 6 tarjetas, rejilla 2 columnas (`space-1` … `space-6`) |
| Paquetes | `#paquetes` | Video de fondo + 3 tarjetas de paquete |
| Historias reales | `#historias` | 3 video-testimonios con nombres de las parejas |
| Galería | `#galeria` | Rejilla de 6 recuadros con spans (`gallery-1` … `gallery-6`) |
| Ubicación | `#ubicacion` | Texto + mapa |
| Footer | — | 3 links de WhatsApp, redes, navegación |
| `<script type="text/x-dc">` | — | La clase `Component` |

### Paleta

| Color | Uso |
|---|---|
| `#b68235` / `#7d5411` | Dorado — acentos, focus, enlaces |
| `#8DB4B7` / `#4F7E82` | Verde agua — footer, botones |
| `#201f1d` | Texto principal |
| `#f3f2f2` / `#eae7e7` | Fondos claros |

---

## 5. Convenciones al editar

1. **Todos los estilos son inline.** Para pisarlos desde el `<style>` hace falta
   `!important`. Ya hay bloques scopeados a `#contacto` y `#galeria` que funcionan así.
2. **No crear `tailwind.config.js` ni instalar dependencias.** No aplica a este proyecto.
3. **No editar `support.js` ni `image-slot.js`.** Son el runtime del export.
4. **Verificar antes de dar por hecho un cambio:**
   ```bash
   curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8080/index.html
   ```
5. **Commits en español**, estilo `fix:` / `feat:` como el historial existente.
6. Los textos de venta los define el cliente por WhatsApp. **No inventar copy.** Si falta
   un dato (un nombre, un número), preguntar en vez de rellenar.

---

## 6. Imágenes y video — el flujo obligatorio

Las fotos llegan a `~/Downloads` como archivos de cámara de 10–16 MB o como imágenes de
WhatsApp. **Nunca se referencian directamente.** Siempre a WebP primero.

### Fotos grandes (archivo de cámara, >2000 px)

```bash
sips -Z 2000 origen.jpg --out /tmp/tmp.png
cwebp -q 82 -quiet /tmp/tmp.png -o assets/nombre-descriptivo.webp
```

Reduce ~96%: 10 MB → 260 KB.

### Fotos que ya vienen de WhatsApp (~1100 px)

**No usar `sips -Z 2000`**: las *amplía* y el WebP sale más pesado que el original. Van
directo, con calidad más baja:

```bash
cwebp -q 75 -quiet "WhatsApp Image ....jpeg" -o assets/nombre.webp
```

### Nombres

Descriptivos y en kebab-case: `galeria-novios-bosque.webp`, no `IMG_6125.jpg` ni
`Gemini_Generated_Image_n97x...`.

### Video

```bash
ffmpeg -i entrada.mp4 -c:v libx264 -crf 29 -preset slow -pix_fmt yuv420p \
       -c:a aac -b:a 96k -movflags +faststart salida.mp4
```

- `-an` en lugar del audio si el video va `muted` + `autoplay` (hero y paquetes).
- `+faststart` siempre: permite reproducir mientras descarga.
- **Si el video ya está comprimido, recomprimirlo lo agranda.** Comparar antes de reemplazar.

### Posters

Los videos de testimonios llevan `preload="none"`, así que sin `poster` se ven negros:

```bash
ffmpeg -ss 3 -i assets/historia-video-N.mp4 -frames:v 1 /tmp/p.png
cwebp -q 80 /tmp/p.png -o assets/poster-historia-N.webp
```

No tomar el segundo 0: suele ser un fundido desde negro.

### Orientación en la galería

La rejilla mezcla recuadros anchos y altos. Meter una foto vertical en un recuadro ancho
la decapita.

| Recuadros | Forma | Va aquí |
|---|---|---|
| `gallery-1` (3×3), `gallery-2` (3×2), `gallery-3` (3×2) | anchos | fotos horizontales |
| `gallery-4`, `gallery-5`, `gallery-6` (2×3) | altos | fotos verticales |

---

## 7. Historial de trabajo

Punto de partida: export de Claude Design con textos genéricos, imágenes sin procesar y
varios huecos.

**Layout y responsive**
- El contenedor raíz tenía `max-width:1600px`, que encajonaba el sitio con bandas grises en
  monitores anchos. Ahora es fluido.
- Se agregaron media queries (1024px y 640px) con `!important`, porque el export no traía
  ninguna y los paddings de 56px y las rejillas de 6 columnas se rompían en pantallas chicas.

**Rendimiento — de ~70 MB a ~3.5 MB de carga inicial**
- Fotos: 25 MB → 990 KB. Eran archivos de cámara de 5296×3535 px en tarjetas de 600 px.
- Videos: recodificados a CRF 29. El hero pasó de 12 MB a 2.7 MB. Estaban a 854×480 pero con
  bitrate de ~3 Mbps, unas 6× de más.
- `preload="none"` + poster en los 3 testimonios: antes el navegador tocaba 27 MB al cargar.
- Google Fonts pasó de `@import` (bloqueante, en serie) a `<link>` con `preconnect`.

**Formulario de contacto**
- Rediseñado: campos de línea editorial en vez de cajas grises, labels en versalitas, radios
  dorados de marca en rejilla 2×2 en lugar de los radios azules del sistema.
- El botón era teal pálido con texto blanco y no pasaba contraste AA. Ahora es `#4F7E82`.
- Se eliminó la animación de despliegue: había un `IntersectionObserver` que abría la tarjeta
  y un `setTimeout` de 450 ms que expandía el contenido. Ahora nace abierto.

**Contenido**
- "Jardín de piedra" → "Plaza de piedra"; "Terraza de la hacienda" → "Hacienda".
- Espacios pasó de 4 a 6 tarjetas (se agregaron "Sesión fotográfica" y "Entrega de anillo").
- Paquetes: "Bodas destino" y "Bodas elite" reescritos a "Bodas Destino · Tres días para
  celebrar" y "Bodas Boutique · Un gran día entre los tuyos".
- Título de la galería: "La hacienda en imágenes" → "Bodas en Ikaan".
- Los testimonios llevan nombres de pareja en vez de "Video de boda — Ikaan Villa Spa".
- Motivo de contacto del formulario: se quitaron "Hospedaje" y "Eventos empresariales", se
  agregaron "Despedida de soltera" y "Otro".

---

## 8. Pendientes

**Datos por confirmar con el cliente**
- El apellido del tercer testimonio está como "Mayagoita". Podría ser "Mayagoitia".
- Falta el nombre de la pareja del **primer** video (dice "Video de boda — Ikaan Villa Spa").
- El link de **WhatsApp · Empresas** del footer está como texto sin enlace, a la espera.
- Verificar que Hospedaje y Bodas no estén invertidos en el footer. Se dedujeron de un chat
  ambiguo: `528181764075` (reservaciones) → Hospedaje, `528116909009` (ejecutivas de eventos)
  → Bodas. Si está al revés, los clientes caen con el ejecutivo equivocado.

**Huecos de imagen**
- `hero-bg` — respaldo del video del hero, solo se ve si el video falla.
- `space-5` (Sesión fotográfica) y `space-6` (Entrega de anillo) — se ven grises siempre.

**Deuda técnica**
- `assets/jardin-piedra.webp` y `assets/terraza.webp` ya no los referencia nadie.
- `assets/galeria-ceremonia-terraza.webp` está convertida pero fuera de la galería: hay 7
  fotos y 6 recuadros.
- El `.git` pesa ~89 MB porque el historial guarda los JPG y videos originales sin
  comprimir, aunque el working tree solo pese 19 MB. Limpiarlo exige reescribir el
  historial.
- `github.md` apunta a `socialmediamqt/Ikaan`, el repo de donde se importó el proyecto. El
  remoto real hoy es `desarrolloweb-hub/ikaanWeb`.
- Origen de una imagen sin aclarar: `explanada-carpas-noche.webp` venía de un archivo llamado
  `Gemini_Generated_Image_...`. Si es generada con IA y no una foto real del venue, conviene
  no usarla para vender el lugar.
