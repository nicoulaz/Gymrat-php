# GYMRAT — Train. Track. Improve.

**Gymrat** es una plataforma digital de fitness y nutrición de alto rendimiento desarrollada en **PHP**, **MySQL**, **JavaScript moderno** y **Vanilla CSS**. Combina conceptos de aplicaciones líderes de la industria (*Hevy, MyFitnessPal, Strava, Strong, Cronometer, Nike Training Club*) bajo una identidad visual atlética, minimalista y oscura (*Carbon Dark Athletic* con acentos verde esmeralda y neón).

---

## ⚡ Características Principales

1. **Autenticación & Onboarding Científico**:
   - Registro seguro con cifrado `password_hash()` (bcrypt).
   - Onboarding interactivo basado en la **fórmula científica Mifflin-St Jeor (1990)** para cálculo dinámico en tiempo real de BMR, TDEE, déficit/superávit y reparto de macronutrientes (Proteínas a 2.0g/kg, Grasas a 0.9g/kg, Carbohidratos para rendimiento).

2. **Dashboard Integral en Tiempo Real**:
   - Resumen calórico y barras proporcionales de macronutrientes.
   - Registro interactivo de agua con actualización en vivo sin recargas (+250ml, +500ml, +750ml).
   - Contador de pasos diarios con estimación de distancia (km) y calorías quemadas.
   - Días de constancia (*Streak*) y nivel de experiencia (XP).

3. **Nutrición & Diario de Comidas**:
   - Desglose por comidas: Desayuno, Media Mañana, Almuerzo, Merienda, Cena y Otros.
   - Base de datos con más de 50 alimentos comunes y macros reales.
   - Buscador dinámico con *debounce* y cálculo automático por gramos/porción.
   - **Simulación funcional de Escáner de Código de Barras**: búsqueda directa en base de datos MySQL local por código EAN/UPC.
   - **Simulación inteligente de Análisis Fotográfico con IA**: segmentación visual de ingredientes con porciones editables antes de guardar.
   - Función para copiar comidas del día anterior con un clic.

4. **Entrenamiento de Fuerza & Modo en Vivo**:
   - Rutinas preconfiguradas: *Push Pull Legs (PPL)*, *Torso / Pierna*, *Full Body* y creador de rutinas personalizadas.
   - Biblioteca técnica con más de 40 ejercicios por grupo muscular con instrucciones, consejos y errores comunes.
   - **Modo Entrenamiento en Vivo**:
     - Cronómetro continuo con persistencia de sesión si se recarga la página.
     - Registro ágil de series con selector de tipo (Normal, Calentamiento, Fallo, Drop Set) y RIR/RPE.
     - **Detección automática de Récords Personales (PRs)** mediante fórmula Epley de 1RM con celebración interactiva.
     - Temporizador de descanso flotante con cuenta regresiva acústica y presets (30s, 60s, 90s, 120s).
     - Función para repetir entrenamientos pasados del historial con 1 clic.

5. **Muscle Map Anatómico 2D**:
   - Representación vectorial interactiva de la musculatura humana (vistas frontal y dorsal).
   - Colorea y resalta en tiempo real los grupos musculares según el volumen real entrenado.

6. **Progreso Corporal & Comparación Fotográfica**:
   - Control de meta de peso con porcentaje completado.
   - **Slider Interactivo Antes / Después** táctil y deslizable para fotografías de progreso.
   - Registro histórico de medidas corporales (cintura, pecho, brazos, muslos).

7. **Calendario Unificado de Hábitos**:
   - Vista mensual que reúne entrenamientos, cumplimiento calórico, agua, pasos y PRs en cada día.

8. **Comunidad Social & Gamificación**:
   - Feed de actividad social con publicaciones de entrenamientos y PRs.
   - Sistema de likes interactivos y seguimiento de atletas (*Follow / Unfollow*).
   - Perfil de atleta con estadísticas consolidadas.
   - Catálogo de logros desbloqueables con recompensas de XP y subida de nivel.

9. **Gymrat PRO (Membresía Demostrativa)**:
   - Presentación comercial con comparativa de planes y activación demostrativa con un clic.

10. **Informe Mensual Imprimible / Exportable**:
    - Reporte analítico con total de sesiones, tiempo, volumen levantado, PRs y promedios nutricionales.

---

## 🚀 Requisitos e Instalación en XAMPP

### Requisitos:
- **XAMPP** para Windows (Apache 2.4+ y PHP 8.0+).
- **MySQL / MariaDB** (InnoDB activado).
- Navegador web moderno (Chrome, Edge, Firefox).

### Paso 1: Ubicación del Proyecto
Colocá la carpeta del proyecto en el directorio `htdocs` de XAMPP:
```
C:\xampp\htdocs\gymrat
```

### Paso 2: Iniciar Servidores en el Panel de XAMPP
1. Abrí el **XAMPP Control Panel**.
2. Hacé clic en **Start** en el módulo **Apache**.
3. Hacé clic en **Start** en el módulo **MySQL**.

### Paso 3: Importar la Base de Datos
1. Abrí tu navegador e ingresá a [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Creá una base de datos llamada `gymrat_db` con cotejamiento `utf8mb4_unicode_ci` (o dejá que el script la cree automáticamente).
3. Hacé clic en la pestaña **Importar** y seleccioná el archivo `database.sql` ubicado en la raíz del proyecto.
4. Hacé clic en **Continuar** para ejecutar la importación.

*(También podés importarla desde la terminal de comandos con: `mysql -u root < database.sql`)*.

### Paso 4: Abrir la Aplicación
Ingresá desde tu navegador a:
```
http://localhost/gymrat
```

---

## 👤 Credenciales de Demostración

La base de datos viene precargada con datos reales de prueba para que la experiencia sea completa desde el primer segundo:

- **Usuario Demo Principal**:
  - **Email**: `demo@gymrat.com`
  - **Contraseña**: `password123`
  - *(O hacé clic en el botón de acceso rápido **"⚡ Iniciar como Nicolás (Usuario Demo)"** en la pantalla de inicio de sesión)*.

- **Otros Atletas de la Comunidad para interactuar**:
  - Valentina Ríos: `valen@gymrat.com` / `password123`
  - Lucas Mendoza: `lucas@gymrat.com` / `password123`
  - Sofía Martínez: `sofia@gymrat.com` / `password123`

---

## 📐 Fórmulas Científicas Implementadas

### 1. Tasa Metabólica Basal (BMR) — Mifflin-St Jeor (1990)
$$\text{Hombres: } BMR = (10 \times \text{peso}_{\text{kg}}) + (6.25 \times \text{altura}_{\text{cm}}) - (5 \times \text{edad}) + 5$$
$$\text{Mujeres: } BMR = (10 \times \text{peso}_{\text{kg}}) + (6.25 \times \text{altura}_{\text{cm}}) - (5 \times \text{edad}) - 161$$

### 2. Gasto Energético Total (TDEE)
$$\text{TDEE} = BMR \times \text{Factor de Actividad (1.2 a 1.9)}$$

### 3. Estimación de 1RM — Epley
$$\text{1RM} = \text{Peso} \times \left(1 + \frac{\text{Reps}}{30}\right)$$

### 4. Volumen de Entrenamiento
$$\text{Volumen} = \text{Peso} \times \text{Repeticiones}$$

---

## 🔒 Arquitectura & Seguridad

- Conexión PDO con prepared statements para evitar inyecciones SQL.
- Encriptación segura de contraseñas con `password_hash()` y `PASSWORD_BCRYPT`.
- Sanitización de salidas para protección XSS.
- Validación de subida de imágenes por tipo MIME real y tamaño máximo.
- APIs REST internas con respuestas JSON estructuradas (`success`, `message`, `data`).
