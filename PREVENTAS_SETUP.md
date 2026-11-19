# Sistema de Preventas - Documentación de Configuración

## ✅ Estado Actual

El sistema de **Preventas (Trial Sales/Preview Sales)** ha sido completamente implementado y configurado en el proyecto Laravel.

## 📁 Estructura de Archivos Creados

### 1. Modelos de Base de Datos
```
app/Models/
├── PreviewSale.php                 # Modelo principal de preventas
├── PreviewSaleDetail.php           # Detalles/líneas de preventas
└── PreviewSalePayment.php          # Pagos asociados a preventas
```

### 2. Componentes Livewire
```
app/Http/Livewire/
├── PreviewSalesController.php      # Listado de preventas con filtros
├── CreatePreviewSale.php           # Crear nueva preventa
├── ShowPreviewSale.php             # Ver detalles y registrar pagos
├── ConfirmPreviewSale.php          # Confirmar preventa y modificar items
└── ReportPreviewSale.php           # Reportes y estadísticas
```

### 3. Vistas Blade
```
resources/views/livewire/preview-sales/
├── index.blade.php                 # Tabla de listado
├── create.blade.php                # Formulario de creación
├── show.blade.php                  # Modal de detalles y pagos
├── confirm.blade.php               # Confirmación con modificación de items
└── report.blade.php                # Dashboard de reportes
```

### 4. Tablas de Base de Datos
- **preview_sales**: Registro principal de preventas
- **preview_sale_details**: Líneas de items en la preventa
- **preview_sale_payments**: Pagos iniciales (opcionales)
- **Columna adicional**: `products.stock_in_trial` para rastrear stock en prueba

## 🔌 Integración en Rutas

Las siguientes rutas están configuradas en `routes/web.php`:

```php
Route::middleware(['auth'])->group(function () {
    Route::get("preview-sales", PreviewSalesController::class)->name('preview-sales');
    Route::get("preview-sales/create", CreatePreviewSale::class)->name('preview-sales.create');
    Route::get("preview-sales/{previewSale}", ShowPreviewSale::class)->name('preview-sales.show');
    Route::get("preview-sales/{previewSale}/confirm", ConfirmPreviewSale::class)->name('preview-sales.confirm-form');
    Route::get("preview-sales/report/all", ReportPreviewSale::class)->name('preview-sales.report');
});
```

## 🎨 Integración en Menú

Se ha agregado un nuevo elemento de menú en **PREVENTAS** en el sidebar principal:
- **Archivo**: `resources/views/layouts/theme/sidebar.blade.php`
- **Ubicación**: Después del menú de VENTAS
- **Icono**: Feather Icon (Clipboard)
- **URL**: `/preview-sales`

### Elemento HTML agregado:
```html
<li class="">
    <a href="{{ url('preview-sales') }}" class="menu-toggle" data-active="false" >
        <div class="base-menu">
            <div class="base-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-clipboard"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
            </div>
            <span>PREVENTAS</span>
        </div>
    </a>
</li>
```

## 📦 Activos CSS/JS Incluidos

Todos los activos necesarios están ya incluidos en `resources/views/layouts/theme/`:

### CSS (en `styles.blade.php`)
- ✅ Bootstrap 5
- ✅ Font Awesome (Feather Icons)
- ✅ SweetAlert CSS
- ✅ Snackbar Notifications
- ✅ Custom Styles

### JavaScript (en `scripts.blade.php`)
- ✅ jQuery 3.1.1
- ✅ Popper.js
- ✅ Bootstrap JS
- ✅ Livewire (configurado en app.blade.php)
- ✅ SweetAlert2
- ✅ Snackbar Notifications
- ✅ Select2
- ✅ FlatPickr (para fechas)

## 🚀 Cómo Acceder

### 1. **Listado de Preventas**
   - URL: `http://localhost/preview-sales`
   - Características:
     - Filtrar por estado, cliente, rango de fechas
     - Ver detalles de cada preventa
     - Acceder a confirmación/modificación
     - Crear nuevas preventas

### 2. **Crear Nueva Preventa**
   - URL: `http://localhost/preview-sales/create`
   - Características:
     - Seleccionar cliente
     - Agregar productos con cantidades
     - Registrar notas
     - Establecer fecha de entrega esperada
     - Registrar pago inicial (opcional)

### 3. **Ver Detalles**
   - URL: `http://localhost/preview-sales/{id}`
   - Características:
     - Ver información completa
     - Registrar pagos adicionales
     - Ver historial de pagos
     - Acceder a confirmación

### 4. **Confirmar Preventa**
   - URL: `http://localhost/preview-sales/{id}/confirm`
   - Características:
     - Agregar/quitar items
     - Modificar cantidades
     - Aplicar descuentos
     - Confirmar para convertir a venta

### 5. **Reportes**
   - URL: `http://localhost/preview-sales/report/all`
   - Características:
     - Estadísticas de preventas
     - Filtros por período
     - Datos de confirmación vs cancelación
     - Análisis de tendencias

## 🔄 Flujo de Preventas

1. **Crear Preventa**
   - Se decrementa el stock del producto
   - Se incrementa `stock_in_trial` del producto
   - Se crea registro en `preview_sales` con estado `pending`

2. **Registrar Pago Opcional**
   - Se puede registrar un pago inicial
   - Se guarda en `preview_sale_payments`

3. **Confirmar Preventa**
   - Se permite modificar items/cantidades
   - Se convierte en una venta normal
   - Se actualiza el estado a `confirmed`
   - Se reduce `stock_in_trial` según lo confirmado

4. **Cancelar Preventa**
   - Se restaura el stock original del producto
   - Se decrementa `stock_in_trial`
   - Se cambia estado a `cancelled`

## 🔐 Permisos y Autenticación

- Todas las rutas están protegidas con `middleware(['auth'])`
- El usuario actual se registra en `created_by_user_id`
- Se registran timestamps de creación y modificación

## 📊 Estructura de Base de Datos

### preview_sales
```
id, customer_id, created_by_user_id, status, expected_delivery_date, 
total, notes, confirmed_at, cancelled_at, created_at, updated_at
```

### preview_sale_details
```
id, preview_sale_id, product_id, quantity, price, notes, created_at, updated_at
```

### preview_sale_payments
```
id, preview_sale_id, amount, payment_type, payment_method, 
delivered_by_user_id, delivery_date, notes, created_at, updated_at
```

## ✨ Características Implementadas

- ✅ Crear preventas con múltiples productos
- ✅ Rastrear stock en prueba separadamente
- ✅ Registrar pagos iniciales (opcionales)
- ✅ Confirmar preventas con capacidad de modificar items
- ✅ Cancelar preventas y restaurar stock
- ✅ Filtrado por estado, cliente y fecha
- ✅ Reportes y estadísticas
- ✅ Auditoría completa (usuario/fecha)
- ✅ Integración con menú principal
- ✅ Componentes Livewire reactivos
- ✅ Validaciones en tiempo real
- ✅ Notificaciones con SweetAlert2 y Snackbar

## 📝 Notas Importantes

1. Los componentes siguen el patrón Livewire de la aplicación (sin controladores tradicionales)
2. Todas las vistas están en `resources/views/livewire/preview-sales/`
3. Los modelos incluyen relaciones con Customer, User y Products
4. Se utiliza `WithPagination` en el listado principal
5. Las fechas se validan y formatean correctamente
6. Los precios se rastrean por item para estadísticas precisas

## 🧪 Próximos Pasos para Pruebas

1. Acceder a `/preview-sales`
2. Crear una preventa de prueba
3. Verificar que el stock se actualiza correctamente
4. Confirmar la preventa
5. Verificar los reportes
6. Confirmar que el menú aparece correctamente en el sidebar

---
**Última actualización**: Sistema completamente implementado y listo para testing
