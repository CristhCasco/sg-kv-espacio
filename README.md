# Despensa y Carniceria Calle 10 - Sistema de Gestión de Ventas y Preventas

Sistema integral para la gestión de ventas, preventas (pedidos anticipados), inventario y créditos. Desarrollado con Laravel 8+ y Livewire para Despensa y Carniceria Calle 10.

## 🎯 Características Principales

### 📋 Sistema de Preventas
- **Crear Preventas**: Registro de pedidos anticipados con productos y cantidades
- **Sincronización de Stock**: El stock se descuenta automáticamente al crear una preventa
- **Confirmación Inteligente**: Las preventas se confirman SOLO cuando se convierte a venta en el POS
- **Historial de Pagos**: Seguimiento de pagos parciales y saldos pendientes
- **Filtros Avanzados**: Búsqueda por estado, cliente, rango de fechas
- **Reportes PDF**: Generación de PDF con detalles de preventa

### 🛒 Sistema POS (Punto de Venta)
- **Carrito de Compras**: Gestión dinámica de productos con descuentos
- **Integración con Preventas**: Carga automática de items desde preventas
- **Múltiples Tipos de Pago**: Efectivo, cheque, crédito con soporte para anticipos
- **Cálculo de IVA**: Soporte para IVA 5% y 10%
- **Facturación Legal**: Generación de facturas oficiales
- **Vuelto Automático**: Cálculo inteligente del cambio

### 💳 Gestión de Créditos
- **Créditos por Cliente**: Seguimiento de límites y saldos disponibles
- **Pagos Parciales**: Registro de abonos a créditos pendientes
- **Sincronización Automática**: Los pagos en el POS se reflejan en las preventas

### 📊 Inventario
- **Control de Stock**: Descuentos automáticos al crear preventas y ventas
- **Diferencias de Inventario**: Registro de ajustes y roturas
- **Reporte de Productos**: Disponibilidad y movimientos de stock

### 👥 Gestión de Clientes
- **Registro de Clientes**: Información completa con límites de crédito
- **Historial de Compras**: Seguimiento de ventas y preventas por cliente
- **Categorías de Clientes**: Clasificación por tipo de cliente

## 🔄 Flujo Sincronizado de Preventa → Venta

```
1. Crear Preventa
   ↓ (Descuenta stock)
2. Cliente en carrito → Estado: PENDIENTE
   ↓
3. Pasar a Ventas (POS)
   ↓ (Preventa sigue PENDIENTE)
4. Completar Venta en POS
   ↓ (Paga anticipo/total)
5. Guardar Venta
   ↓ (Preventa marca CONFIRMADA, actualiza pagos)
6. Sincronización Automática
   ↓
   - Pagado: Gs. X.XXX
   - Pendiente: Gs. Y.YYY
```

## 📱 Tecnologías Utilizadas

- **Laravel 8+**: Framework PHP moderno
- **Livewire**: Componentes reactivos sin escribir JavaScript
- **MySQL**: Base de datos relacional
- **Bootstrap 5**: Interfaz responsiva
- **DOMPDF**: Generación de PDF
- **Maatwebsite Excel**: Exportación a Excel

## 🚀 Instalación

```bash
# Clonar repositorio
git clone https://github.com/CristhCasco/boutique1.1.git

# Instalar dependencias
composer install
npm install

# Configurar archivo .env
cp .env.example .env
php artisan key:generate

# Migrar base de datos
php artisan migrate

# Compilar assets
npm run dev

# Iniciar servidor
php artisan serve
```

## 📋 Requisitos

- PHP 8.0+
- Composer
- Node.js y npm
- MySQL 5.7+
- Laravel 8+

## 🔐 Autenticación

El sistema incluye autenticación integrada con roles de usuario:
- **Admin**: Acceso total al sistema
- **Vendedor**: Acceso a POS y preventas
- **Soporte**: Acceso a reportes

## 📝 Configuración

### Variables de Entorno
- `DB_HOST`: Host de la base de datos
- `DB_DATABASE`: Nombre de la base de datos
- `DB_USERNAME`: Usuario de la base de datos
- `DB_PASSWORD`: Contraseña de la base de datos

### Moneda
La moneda predeterminada es **Guaraní (Gs.)**

## 🐛 Bugs y Mejoras

- [x] Sistema de preventas completamente funcional
- [x] Sincronización preventa → venta
- [x] Control de stock bidireccional
- [x] Pagos parciales sincronizados
- [x] Reportes y PDF

## 🆕 Cambios Recientes (Abril 2026)

### POS y productos por peso
- Modal de pesaje para productos por kilo con selector de unidad (kg/g)
- Normalización de entrada decimal (coma y punto) y bloqueo de teclas inválidas (`e`, `E`, `+`, `-`)
- Validación de peso máximo permitido y foco automático en input del modal

### Validaciones de productos
- Limpieza y centralización de validaciones en `ProductsController`
- Corrección de comportamiento por tipo de producto (`is_weighted`) para evitar errores cruzados de precio
- Ajustes para edición/actualización de productos por unidad y por peso sin falsos positivos de validación

### Alertas de stock bajo
- Alerta automática de stock bajo en POS (notificación visual)
- Acción rápida "VER TODAS" para abrir modal de productos con stock crítico
- Exportación PDF A4 de productos con stock bajo

### Reporte de inventario mejorado
- Filtros activos: stock, tipo de producto, categoría y nombre
- Búsqueda por nombre con auto-filtrado desde la segunda letra
- Eliminación del filtro redundante "En stock (sobre mínimo)"
- Numeración de filas en listado
- Totales de inventario en pantalla:
   - Total invertido (compra × stock)
   - Total cotizado (venta × stock)
   - Ganancia estimada y porcentaje
- Formato monetario unificado en Guaraní (`₲`) sin decimales

### Exportaciones (Excel y PDF)
- Excel y PDF respetan todos los filtros activos del reporte
- PDF de inventario con filtros visibles, numeración y totales
- Excel de inventario con resumen de filtros y resumen de totales al final

## 📞 Soporte

Para reportar bugs o sugerencias, crear un issue en el repositorio.

## 📄 Licencia

Todos los derechos reservados © 2026 Despensa y Carniceria Calle 10

## 👤 Autor

Desarrollado por: **CristhCasco**

---

**Versión**: 1.2  
**Fecha**: Abril 2026  
**Estado**: Producción
