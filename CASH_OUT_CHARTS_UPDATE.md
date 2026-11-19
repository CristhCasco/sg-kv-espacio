# Actualización: Sistema de Gráficos para Corte de Caja

## Resumen de Cambios

Se han agregado gráficos interactivos con Chart.js v3.9.1 a todos los tabs del sistema de **corte de caja** para mejorar la visualización de datos y facilitar el análisis de información.

---

## Gráficos Implementados

### 📊 TAB: RESUMEN (Dashboard Principal)

**Ubicación:** `resources/views/livewire/cashout/tabs/resumen.blade.php`

#### Gráficos:
1. **Doughnut Chart - Contado vs Crédito**
   - ID: `chartResumenContadoCredito`
   - Datos: $totalContado, $totalCredito
   - Colores: Verde (#28a745) | Azul (#0056b3)
   - Posición: Columna Izquierda

2. **Bar Chart - Comparativa: Ventas, Compras, Gastos**
   - ID: `chartResumenComparativa`
   - Datos: $totalSales, $totalPurchases, $totalExtraExpenses
   - Colores: Verde | Azul | Amarillo
   - Posición: Columna Derecha

---

### 💰 TAB: ANÁLISIS DE VENTAS

**Ubicación:** `resources/views/livewire/cashout/tabs/ventas.blade.php`

#### Gráficos:
1. **Doughnut Chart - Contado vs Crédito**
   - ID: `chartContadoCredito`
   - Datos: $totalContado, $totalCredito
   - Colores: Verde (#28a745) | Azul (#0056b3)
   - Posición: Columna Izquierda

2. **Horizontal Bar Chart - Ventas por Método de Pago**
   - ID: `chartPaymentMethod`
   - Datos: Agrupación por métodos (EFECTIVO, TDD, TDC, TRANS, TIGO, CHEQUE, OTROS)
   - Colores: Diversos colores asignados dinámicamente
   - Posición: Columna Derecha

#### Features:
- Filtros por Tipo de Pago (Todos/Contado/Crédito)
- Filtros por Método de Pago (Todos/EFECTIVO/TDD/TDC/etc)
- Tabla de historial de ventas con detalles completos

---

### 💳 TAB: GESTIÓN DE CRÉDITOS

**Ubicación:** `resources/views/livewire/cashout/tabs/creditos.blade.php`

#### Gráficos:
1. **Doughnut Chart - Estado General de Créditos**
   - ID: `chartCreditStatus`
   - Datos: $totalCreditPaid, ($totalCreditIssued - $totalCreditPaid)
   - Colores: Verde (#28a745) | Rojo (#dc3545)
   - Etiquetas: "Pagado" | "Emitido pero No Cobrado"
   - Posición: Columna Izquierda

2. **Pie Chart - Proporción Pagado vs Pendiente**
   - ID: `chartCreditRatio`
   - Datos: $totalCreditPaid, $totalCreditPending
   - Colores: Verde (#28a745) | Amarillo (#ffc107)
   - Etiquetas: "Pagado" | "Pendiente"
   - Posición: Columna Derecha

#### Features:
- Tabla de créditos pendientes con detalles de clientes
- Cálculo automático de saldo pendiente
- Badges de estado (Pendiente/Pagado)

---

### 📈 TAB: ANÁLISIS DE RENTABILIDAD

**Ubicación:** `resources/views/livewire/cashout/tabs/rentabilidad.blade.php`

#### Gráficos:
1. **Horizontal Bar Chart - Top 5 Productos Más Rentables**
   - ID: `chartTopProducts`
   - Datos: $topProducts (iteración con cálculo de ganancia)
   - Cálculo: (total_sold - total_cost)
   - Color: Verde (#28a745)
   - Posición: Columna Izquierda

2. **Bar Chart - Rentabilidad por Categoría**
   - ID: `chartCategoryProfit`
   - Datos: $categoryProfitability (ganancia por categoría)
   - Cálculo: (total_sold - total_cost) por categoría
   - Colores: Diversos (asignados dinámicamente)
   - Posición: Columna Derecha

#### Features:
- Métricas de margen bruto, margen neto, ROI
- Tablas detalladas con cálculos de ganancia
- Análisis de margen porcentual por producto

---

## Estructuras de Datos

### Datos que Alimentan los Gráficos

```php
// Totales Generales
$totalSales          // Total vendido
$totalContado        // Venta de contado
$totalCredito        // Venta a crédito
$itemsSales          // Cantidad de artículos

// Créditos
$totalCreditIssued   // Créditos emitidos
$totalCreditPaid     // Créditos cobrados
$totalCreditPending  // Créditos pendientes

// Compras y Gastos
$totalPurchases      // Total de compras
$totalExtraExpenses  // Gastos extras

// Rentabilidad
$topProducts         // Array de top 5 productos
$categoryProfitability // Array de rentabilidad por categoría

// Profitabilidad
$grossMarginPercent  // Margen bruto porcentaje
$netMarginPercent    // Margen neto porcentaje
$roi                 // Retorno sobre inversión
```

---

## Opciones de Configuración Chart.js

Todos los gráficos utilizan las siguientes configuraciones:

```javascript
options: {
    responsive: true,                    // Adapta al contenedor
    maintainAspectRatio: true,           // Mantiene proporción
    plugins: {
        legend: {
            position: 'bottom',           // Leyenda abajo
            labels: {
                font: { size: 12 },
                padding: 15
            }
        }
    }
}
```

---

## Colores Utilizados

| Color | Hex | Uso |
|-------|-----|-----|
| Verde | #28a745 | Contado, Pagado, Ganancia |
| Azul | #0056b3 | Crédito, Compras |
| Amarillo | #ffc107 | Gastos Extras, Pendiente |
| Rojo | #dc3545 | Crédito Pendiente, Estado Negativo |
| Marrón | #856404 | Crédito Emitido, Costo |
| Gris | #6c757d | Valores Neutrales |
| Marón Oscuro | #620408 | Encabezados Tablas |

---

## Integración con Livewire

Los gráficos se renderizan automáticamente cuando:
1. Se carga el componente del corte de caja
2. Se ejecuta la consulta (botón Consultar)
3. Se cambian los filtros y se consulta nuevamente

El evento `DOMContentLoaded` asegura que Chart.js esté disponible antes de inicializar los gráficos.

---

## Responsive Design

- **Desktop (>768px):** 2 columnas de gráficos
- **Tablet (768px):** 2 columnas reducidas
- **Mobile (<576px):** 1 columna con gráficos apilados

Los gráficos se adaptan automáticamente gracias a `responsive: true` en Chart.js.

---

## Mejoras Futuras

- [ ] Agregar exportación a PDF con gráficos
- [ ] Agregar exportación a Excel
- [ ] Implementar comparativa período vs período anterior
- [ ] Agregar filtros de fecha con presets (Hoy, Esta Semana, Este Mes, etc)
- [ ] Implementar caching para optimizar rendimiento con grandes datasets
- [ ] Agregar gráficos de tendencia (líneas) para evolución temporal

---

## Archivos Modificados

1. **resources/views/livewire/cashout/tabs/resumen.blade.php**
   - Agregados 2 gráficos con scripts

2. **resources/views/livewire/cashout/tabs/ventas.blade.php**
   - Agregados 2 gráficos con scripts

3. **resources/views/livewire/cashout/tabs/creditos.blade.php**
   - Agregados 2 gráficos con scripts

4. **resources/views/livewire/cashout/tabs/rentabilidad.blade.php**
   - Agregados 2 gráficos con scripts

---

## Librería Utilizada

**Chart.js v3.9.1** - CDN
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
```

Incluida en: `resources/views/layouts/theme/scripts.blade.php`

---

## Verificación

Para verificar que los gráficos funcionen correctamente:

1. Navegar a "Corte de Caja"
2. Seleccionar usuario y rango de fechas
3. Hacer clic en "Consultar"
4. Verificar que aparezcan gráficos en cada tab:
   - ✅ Tab Resumen: 2 gráficos
   - ✅ Tab Análisis de Ventas: 2 gráficos
   - ✅ Tab Créditos: 2 gráficos
   - ✅ Tab Rentabilidad: 2 gráficos

---

**Fecha de Implementación:** 2024
**Versión de Chart.js:** 3.9.1
**Framework:** Laravel + Livewire + Bootstrap 4
