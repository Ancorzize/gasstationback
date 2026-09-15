# GASSTATION — VALIDATOR CONTEXT

## 1. Descripción del proyecto
GASSTATION es un software ERP especializado en la administración y operación de estaciones de servicio/gasolineras. Actualmente funciona como un desarrollo a la medida para un cliente específico.

## 2. Objetivo estratégico
El objetivo estratégico futuro es transformar el proyecto progresivamente en una plataforma configurable y multiempresa/multicliente (SaaS). Se busca que un SUPERADMINISTRADOR pueda crear nuevas cuentas/clientes y habilitar módulos o funcionalidades específicas sin necesidad de instalar una nueva base de datos, backend o frontend, y sin modificar el código fuente.

## 3. Estado actual
Actualmente el sistema opera para un único cliente y no ha sido migrado al modelo multiempresa. Existen datos y configuraciones operativas que pueden estar quemados en el código. La conversión a multiempresa y la parametrización por cliente/marca se realizará en etapas futuras.

## 4. Stack tecnológico
Confirmado a partir de los archivos de configuración inspeccionados:
- **Backend**:
  - PHP: ^8.2
  - Framework: Laravel ^12.0
  - Autenticación: Laravel Sanctum ^4.0
  - Permisos/Roles: Spatie Laravel Permission ^6.25
  - Generación de PDF: barryvdh/laravel-dompdf ^3.1
- **Frontend**:
  - Framework/Librería: React ^19.2.0
  - Enrutamiento: React Router DOM ^7.13.1
  - Bundler/Build tool: Vite (^7.3.1 en `front/`, plugin Laravel Vite ^2.0.0 en raíz)
  - Estilos: TailwindCSS (^3.4.13 en `front/`, ^4.0.0 en raíz), PostCSS, Autoprefixer
  - Componentes/UI/Utilidades: Lucide React, Framer Motion, Recharts, React Select, jsPDF, XLSX, Lodash
- **Mobile Integration**:
  - Capacitor Core / CLI / Android (^8.5.1) en `front/`

## 5. Arquitectura backend
- **Estructura modular por capas**: Ubicada en `app/Modules/`, dividida en módulos funcionales (ej. `Auth`, `Usuarios`, `Clientes`, `Proveedores`, `Roles`, `Compras`, `Ventas`, `Estaciones`, `Bombas`, `Mangueras`, `TurnosIslero`, `Inventarios`, `Caja`, `Gastos`, `Cartera`, `Dashboard`, etc.).
- **Capas por módulo**: Cada módulo organiza sus clases en subcarpetas de capa (`Application`, `Infrastructure`, `Presentation`).
- **Directorios base Laravel**: `app/Http`, `app/Models`, `app/Providers`, `app/Shared`.
- **Rutas API**: Centralizadas en `routes/api.php`, organizadas mediante prefijos por módulo y mayoritariamente resguardadas tras el middleware `auth:sanctum`.

## 6. Frontend
- **Ubicación**: El código del frontend React/Vite está dentro del proyecto en la carpeta `front/`.
- **Estructura de `front/src/`**:
  - `components/`: Componentes UI reutilizables.
  - `context/`: Contextos globales de estado React.
  - `features/`: Módulos o funcionalidades específicas del cliente/UI.
  - `hooks/`: Custom hooks.
  - `layouts/`: Diseños y plantillas de estructura.
  - `shared/`: Recursos y utilidades compartidas.
  - `assets/` / `images/`: Archivos multimedia e imágenes.

## 7. Autenticación y autorización
- **Autenticación**: Basada en Laravel Sanctum (tokens Bearer / estado de sesión SPA). Endpoint público `/api/auth/login` y endpoints protegidos como `/api/auth/me` y `/api/auth/logout`.
- **Autorización**: Control de acceso basado en roles y permisos gestionado vía Spatie Laravel Permission (endpoints `/api/roles`, `/api/permisos`, `/api/auth/me/permissions`).

## 8. Objetivos futuros
- Plataforma multiempresa / multicliente.
- Configuración dinámica por cliente sin duplicar base de datos, backend ni frontend.
- Identidad visual y configuraciones operativas parametrizables por marca/cliente.
- Habilitación modular de funcionalidades por cliente administrada por el SUPERADMIN.
- Eliminación progresiva de valores quemados en código.
- Operación multitenant sobre una única instalación/plataforma.

## 9. Reglas para futuras modificaciones
- No modificar funcionalidades fuera del alcance solicitado.
- Analizar únicamente los archivos indicados para cada tarea.
- Evitar análisis innecesarios del proyecto.
- No crear pruebas automáticas salvo solicitud explícita.
- Mantener la arquitectura existente.
- No hacer refactorizaciones no solicitadas.
- Ante errores, inconsistencias o bloqueos, detenerse y reportar sin intentar solucionarlos.
- Actualizar `VALIDATOR_CONTEXT.md` progresivamente después de cada tarea.
- No asumir que una funcionalidad existe sin verificarla.
- No eliminar código existente sin autorización explícita.

## 10. Turnos de Islero

### Propósito
Representa el turno operativo de trabajo de un operador de la estación de servicio (islero). Controla el período de atención, el conteo de galones por manguera, las ventas realizadas, el recaudo por medios de pago/destinos, las ventas a crédito, los abonos a cartera y el cierre con arqueo y movimientos de caja.

### Flujo de estados
Los estados del turno en el código son exactamente:
1. `abierto`: El turno está activo y el islero realiza operaciones.
2. `pendiente_cierre`: El islero ha solicitado el cierre enviando sus lecturas finales de manguera y declaración de recaudos. No genera aún movimientos de caja ni ajuste de inventario.
3. `cerrado`: El administrador (o usuario autorizado) revisa y efectúa el cierre definitivo. Se generan la venta de ajuste AJT de combustible, los descuentos de inventario y los ingresos a caja.

### Apertura
- **Quién**: Usuario autenticado con permiso `abrir_turnos_islero`.
- **Mecanismo**: Endpoint `POST /api/turnos-islero/abrir`. Se valida que el usuario no tenga ya un turno `abierto`.
- **Validaciones**: Mangueras activas de la estación no ocupadas en otros turnos abiertos, asignación de mangueras al turno, y registro de `lectura_inicial` (tomada automáticamente del último turno cerrado/pendiente de la manguera o enviada explícitamente si no posee antecedente). Asigna el precio vigente de combustible.

### Operación durante el turno
El islero con turno `abierto` puede:
- Realizar ventas de combustible (módulo ventas, `tipo_origen = 'combustible'`).
- Realizar ventas de lubricantes (módulo ventas POS, `tipo_origen = 'pos'`).
- Realizar ventas a crédito.
- Registrar abonos a cartera (`AbonoCartera` asociado a `turno_islero_id`).
- Consultar resumen previo al cierre (`resumenCierre`).
- Enviar solicitud de cierre (`solicitarCierre`).

### Combustible
- **Lecturas**: Registra `lectura_inicial` al abrir y `lectura_final` al solicitar cierre/cerrar.
- **Galones vendidos físicos**: `lectura_final - lectura_inicial`.
- **Total venta física**: `galones_vendidos * precio_galon`.
- **Venta de Ajuste (AJT)**: En el cierre definitivo (`cerrar()`), se compara los galones físicos con la suma de galones de ventas registradas en el sistema para cada manguera. Si los galones físicos superan a los del sistema, la diferencia (`galonesAjuste`) descuenta inventario en la bodega del usuario y se invoca `crearVentaAjusteTurno` para generar la venta de ajuste que nivela el sistema con lo vendido por lecturas físicas.

### Lubricantes
- Las ventas de lubricantes se efectúan vía punto de venta (`tipo_origen = 'pos'`).
- Descuentan inventario en el momento exacto en que se efectúa la venta individual.
- Se consolidan en el turno para el cálculo del total de ventas y recaudo esperado.

### Crédito
- Se registran en el módulo de ventas asociadas al `turno_islero_id`, acumulando un `saldo_pendiente`.
- Se restan del recaudo esperado en el arqueo del turno (`total_recaudo_esperado = total_combustible_fisico + total_lubricantes + total_abonos - total_creditos`).

### Abonos
- Se registran como `AbonoCartera` asignados al `turno_islero_id`.
- Incrementan el dinero total esperado/reportado del turno.
- En el cierre definitivo, si el turno pertenece a un islero, se crean movimientos de caja de tipo `ingreso` categoría `abono_cartera` para los abonos registrados.

### Caja
- **Regla fundamental**: Mientras el turno está `abierto` o `pendiente_cierre`, NO se generan movimientos de caja por las ventas u operaciones del turno.
- **Generación de movimientos**: Únicamente ocurren al ejecutarse el cierre definitivo (`cerrar()`), cuando el turno pasa a estado `cerrado`. Se crean registros `MovimientoCaja` (`tipo_movimiento = 'ingreso'`, `categoria_movimiento = 'cierre_turno'`, `origen_modulo = 'turnos_islero'`) por cada destino de recaudo y medio de pago reportado.

### Cierre
1. **Solicitud de Cierre** (`solicitarCierre()`):
   - Ejecutado por el islero.
   - Pasa estado de `abierto` a `pendiente_cierre`.
   - Registra lecturas finales y desglose de recaudos declarados por el islero.
   - No genera movimientos de caja ni de inventario.
2. **Revisión de Cierre** (`revisionCierre()`):
   - Endpoint administrativo para inspeccionar lecturas, resumen de ventas, recaudos y diferencias en turnos `pendiente_cierre`.
3. **Cierre Definitivo** (`cerrar()`):
   - Ejecutado por un administrador / usuario autorizado sobre turnos en `pendiente_cierre`.
   - Valida/actualiza lecturas finales y recaudos confirmados.
   - Procesa diferencia de galones y descuenta inventario de combustible.
   - Crea la venta de ajuste AJT.
   - Calcula totales, recaudo esperado y balance final (`total_reportado - total_recaudo_esperado`).
   - Setea `estado = 'cerrado'` y `fecha_cierre = now()`.
   - Registra todos los movimientos de caja correspondientes.

### Reglas de negocio importantes
- Solo se permite 1 turno abierto simultáneamente por usuario (`user_id`).
- Una manguera no puede estar asignada a más de 1 turno abierto al mismo tiempo.
- No se puede cerrar un turno si existe un turno anterior sin cerrar que comparta alguna manguera.
- El cierre definitivo solo es ejecutable sobre turnos en estado `pendiente_cierre`.
- Los movimientos de caja de las ventas del turno nunca se generan de forma inmediata durante la operación, sino de forma consolidada al cerrar el turno.
- El combustible físico no registrado por ventas individuales durante el turno se ajusta automáticamente al cerrar mediante la venta AJT y el descuento de inventario por la diferencia.

### Diferencias detectadas
- **Ninguna**. La implementación en código `COINCIDE` plenamente con la descripción funcional proporcionada por el responsable del proyecto (flujo `abierto` → `pendiente_cierre` → `cerrado`, suspensión de movimientos de caja hasta el cierre definitivo, venta de ajuste de combustible y manejo de créditos/abonos).

### Reglas para futuras tareas en Turnos de Islero
- Este módulo no debe modificarse fuera del alcance solicitado.
- Antes de modificar TurnosIslero se debe revisar `VALIDATOR_CONTEXT.md`.
- Las modificaciones futuras deben preservar las reglas de negocio existentes salvo que el responsable solicite explícitamente cambiar alguna.
- El contexto debe actualizarse después de cada modificación relevante.
- No realizar refactorizaciones no solicitadas.
- No crear pruebas automáticas salvo solicitud explícita.
- Ante errores, inconsistencias o bloqueos, detenerse y reportar.

## 11. Dependencias de Turnos Islero

### Ventas
TurnosIslero se conecta con el módulo Ventas mediante la columna `turno_islero_id` en la tabla `ventas`.
- **Ventas Lubricantes (POS)**: Creadas vía `VentaService::create()` (`tipo_origen = 'pos'`), descuentan inventario inmediatamente al venderse y quedan asociadas al turno abierto del usuario.
- **Ventas Combustible**: Creadas vía `VentaService::createCombustible()` (`tipo_origen = 'combustible'`). Al cerrar el turno, si los galones físicos de manguera exceden las ventas del sistema, se genera automáticamente una Venta de Ajuste (`tipo_origen = 'ajuste_turno'`, prefijo `'AJT'`).
- **Anulación**: `VentaService::anular()` permite anular ventas de un turno mientras este siga en estado `abierto` o `pendiente_cierre`. Al anularse, se devuelven inventarios, se revierten saldos de cartera y se invocan los métodos de recálculo de totales del turno (`recalcularTotalesTurno`).

### Cartera
TurnosIslero se conecta con Cartera de dos formas:
- **Ventas a Crédito**: Al realizarse una venta POS o de combustible a crédito (`saldo_pendiente > 0`), se incrementa `cliente.saldo_credito`, se genera `MovimientoCartera` (`tipo_movimiento = 'venta_credito'`) y el saldo pendiente suma al total de créditos del turno (`total_creditos`), restando del recaudo esperado.
- **Abonos de Cartera**: Registrados vía `CarteraService::registrarAbono()`, asociando `turno_islero_id` si el usuario tiene turno abierto. El abono se aplica en orden cronológico (FIFO) a las ventas/saldos iniciales pendientes, actualiza `cliente.saldo_credito` y genera `MovimientoCartera` (`tipo_movimiento = 'abono'`). Los abonos suman a `total_abonos` del turno.

### Inventario
- **Salidas por Lubricantes**: Se realiza el descuento directo de stock en `inventarios` y se registra `MovimientoInventario` (`tipo_movimiento = 'venta'`) al momento exacto de crear la venta POS.
- **Salidas por Combustible (AJT)**: Durante el cierre definitivo del turno (`cerrar()`), se calcula la diferencia entre galones por lecturas de manguera y galones registrados en el sistema. Si existe sobrante físico, se descuenta de `inventarios` en la bodega del usuario y se crea `MovimientoInventario` (`tipo_movimiento = 'venta_combustible'`).
- **Reingresos por Anulación**: Al anular una venta de lubricantes, se incrementa el stock en `inventarios` y se registra `MovimientoInventario` (`tipo_movimiento = 'anulacion_venta'`).

### Caja
- **Regla de postergación**: Durante el turno `abierto` o `pendiente_cierre`, las ventas y abonos del islero **NO** generan registros inmediatos en `movimientos_caja`.
- **Impacto al cierre definitivo**: Únicamente cuando el turno pasa a estado `cerrado`, `TurnoIsleroService` ejecuta:
  - `registrarMovimientosCaja()`: Crea registros en `movimientos_caja` (`categoria_movimiento = 'cierre_turno'`, `origen_modulo = 'turnos_islero'`) agrupados por destino de recaudo y medio de pago.
  - `registrarMovimientosCajaAbonos()`: Crea registros en `movimientos_caja` (`categoria_movimiento = 'abono_cartera'`, `origen_modulo = 'cartera'`) por los abonos del turno.

### Mapa de operaciones
- **Venta Lubricante (POS)**:
  `Venta` (POS) → `DetalleVenta` → `PagoVenta` → `Inventario` (decremento) → `MovimientoInventario` (venta) → `TurnoIslero` (suma `total_ventas_lubricantes`) → `Caja` (al cerrar el turno).
- **Venta Crédito**:
  `Venta` → `Cliente` (`saldo_credito` +) → `MovimientoCartera` (`venta_credito`) → `TurnoIslero` (suma `total_creditos`, resta de recaudo esperado).
- **Abono Cartera**:
  `AbonoCartera` → `AbonoCarteraDetalle` / `AplicacionAbonoSaldoInicial` → `Venta` (`saldo_pendiente` -) → `Cliente` (`saldo_credito` -) → `MovimientoCartera` (`abono`) → `TurnoIslero` (suma `total_abonos`) → `Caja` (al cerrar el turno).
- **Combustible / Venta AJT**:
  `LecturaManguera` (física vs sistema) → `Inventario` (descuento diferencia) → `MovimientoInventario` (`venta_combustible`) → `Venta` (AJT) → `DetalleVenta` → `TurnoIslero` (ajusta ventas e inventarios) → `Caja` (al cerrar el turno).

### Correcciones futuras (OBJETIVO FUTURO — NO IMPLEMENTADO AÚN)
Como objetivo futuro, el administrador deberá poder corregir operaciones individuales (ventas, deudas a crédito o abonos a cartera específicos) durante la fase de revisión del turno `pendiente_cierre`. Al corregir una operación individual, el sistema deberá re-ejecutar en cascada la actualización de los saldos del cliente, aplicaciones a facturas, movimientos de cartera, inventarios y el recálculo automático de los totales derivados del turno (`recalcularTotalesTurno`). Actualmente esta funcionalidad de edición/corrección individual **NO está implementada en el sistema**.

## 12. Corrección y anulación de operaciones de Turnos Islero

### Objetivo
Permitir que un usuario administrador, durante la fase de revisión de un turno de islero, pueda inspeccionar, editar o anular operaciones individuales (ventas POS de lubricantes, ventas a crédito, abonos a cartera y ventas de combustible) sin alterar manualmente los totales agregados del turno. Los totales del turno se mantendrán siempre como valores derivados recalculados automáticamente a partir de las transacciones individuales reales.

### Estados y Permisos
- **PENDIENTE_CIERRE**: Único estado en el cual están permitidas las acciones de **EDITAR** y **ANULAR** operaciones asociadas al turno.
- **ABIERTO**: La operación se realiza de forma normal por el islero; no se aplica la corrección administrativa.
- **CERRADO**: El turno y sus operaciones quedan en modo **SOLO CONSULTA**. No se permite editar ni anular ninguna transacción posterior al cierre definitivo.
- **Restricción**: Esta regla de validación debe aplicarse a nivel de **BACKEND** en los servicios correspondientes, no únicamente en la interfaz de usuario.

### Operaciones Individuales Sujetas a Corrección
1. **Lubricantes (Ventas POS)**: Ventas de insumos/lubricantes registradas durante el turno.
2. **Ventas a Crédito**: Operaciones con saldo pendiente asignadas a un cliente con cupo de crédito habilitado.
3. **Abonos de Cartera**: Pagos recibidos de clientes para amortizar deudas pendientes.
4. **Combustible**: Lecturas de mangueras y ventas registradas de combustible.

### Dependencias y Efectos Secundarios por Operación
- **Venta POS Lubricantes**: Afecta `ventas`, `detalle_ventas`, `pagos_venta`, `inventarios` (stock), `movimientos_inventario` y los totales del turno (`total_ventas_lubricantes`).
- **Venta a Crédito**: Afecta `ventas`, `cliente.saldo_credito`, `movimientos_cartera` (`venta_credito`) y los totales del turno (`total_creditos`).
- **Abono de Cartera**: Afecta `abonos_cartera`, `abono_cartera_detalles` (o `aplicacion_abono_saldo_inicial`), `venta.saldo_pendiente`, `venta.estado_pago`, `cliente.saldo_credito`, `movimientos_cartera` (`abono`) y los totales del turno (`total_abonos`).
- **Combustible y AJT**: Afecta `lecturas_manguera`, `inventarios` (diferencia física), `movimientos_inventario` (`venta_combustible`), `ventas` (sintética AJT) y los totales del turno.

### Anulación de Operaciones

#### Estado Actual (IMPLEMENTADO)
- `VentaService::anular()`: Permite anular ventas de lubricantes o crédito si el turno no está en estado `cerrado`. Revierte el stock en `inventarios`, crea `movimientos_inventario` (`anulacion_venta`), resta el saldo en `cliente.saldo_credito`, crea `movimientos_cartera` (`anulacion`) y recalcula los totales del turno (`recalcularTotalesTurno`). Endpoint: `POST /api/ventas/{id}/anular`.
- `CarteraService::anularAbono()`: Permite anular un `AbonoCartera` si el turno no está en estado `cerrado`. Revierte aplicaciones FIFO sobre `ventas` y `saldos_iniciales_cartera`, incrementa `cliente.saldo_credito`, registra `MovimientoCartera` (`anulacion_abono`), actualiza `abono.estado = 'anulado'` y recalcula los totales del turno (`recalcularTotalesTurno`). Endpoint: `POST /api/cartera/abonos/{id}/anular`.

### Edición de Operaciones

#### Estado Actual (IMPLEMENTADO)
- `VentaService::update()`: Permite editar ventas POS de lubricantes (cantidades, productos, cliente, observacion) si el turno no está en estado `cerrado` (`abierto` y `pendiente_cierre` permitidos). Ajusta stock de inventarios (`ajuste_edicion_venta`), recalcula subtotales y totales. Captura el `oldSaldoPendiente` antes de modificar la venta, y actualiza de forma exacta `cliente.saldo_credito` aplicando únicamente la diferencia de saldo pendiente cuando el cliente se mantiene igual (o revirtiendo y asignando saldos cuando cambia el cliente). Bloquea la edición si la venta a crédito ya tiene abonos aplicados y recalcula los totales del turno (`recalcularTotalesTurno`). Endpoint: `PUT /api/ventas/{id}`.
- `CarteraService::updateAbono()`: Permite editar un `AbonoCartera` (monto, medio de pago, observación) si el turno no está en estado `cerrado` (`abierto` y `pendiente_cierre` permitidos). Revierte temporalmente las aplicaciones FIFO anteriores, actualiza el valor y re-aplica el abono en orden FIFO sobre deudas abiertas, actualiza `cliente.saldo_credito`, crea `MovimientoCartera` (`edicion_abono`) y recalcula los totales del turno (`recalcularTotalesTurno`). Endpoint: `PUT /api/cartera/abonos/{id}`.
- **Edición de Lecturas de Combustible**: Soportado en la revisión de cierre del turno actualizando `lecturas_finales`.

### Reglas de Estados del Turno para Edición y Anulación
- `abierto`: Permitido (para permitir edición/anulación operativa y correcciones antes de la revisión).
- `pendiente_cierre`: Permitido (para revisión administrativa).
- `cerrado`: Bloqueado (solo lectura, prohíbe cualquier edición o anulación).

### Auditoría y Trazabilidad
- **Estado Actual (IMPLEMENTADO)**: `Venta` guarda `motivo_anulacion`, `user_anulacion_id` y `fecha_anulacion`. `AbonoCartera` actualiza `estado = 'anulado'` y su observación. `MovimientoCartera` registra movimientos de tipo `anulacion_abono`, `edicion_abono`, `ajuste_edicion_venta` y `anulacion`. `MovimientoInventario` registra movimientos de tipo `ajuste_edicion_venta` y `anulacion_venta`.

### Transacciones DB
- **Estado Actual (IMPLEMENTADO)**: Todos los servicios principales (`TurnoIsleroService`, `VentaService`, `CarteraService`, `CajaService`, `MovimientoInventarioService`) emplean `DB::transaction(...)` para garantizar consistencia atómica.

### Endpoints Relevantes
- **Existentes Relevantes**:
  - `GET /api/turnos-islero/pendientes-cierre`
  - `GET /api/turnos-islero/{id}/revision-cierre`
  - `GET /api/turnos-islero/{id}/operaciones`
  - `GET /api/turnos-islero/{id}/operaciones/{tipo}`
  - `POST /api/turnos-islero/{id}/cerrar`
  - `POST /api/ventas/{id}/anular`
  - `PUT /api/ventas/{id}`
  - `POST /api/cartera/abonos/{id}/anular`
  - `PUT /api/cartera/abonos/{id}`

## 13. Endpoints de Consulta, Edición y Anulación de Operaciones de Turno Islero

### IMPLEMENTADO:
1. **Resumen de Operaciones**: `GET /api/turnos-islero/{id}/operaciones`
   - Devuelve las cantidades y totales reales calculados desde las operaciones del turno para: `combustible`, `lubricantes`, `creditos`, `abonos`, junto al `total_general` y la información básica del turno.
   - Permitido para turnos en estado `abierto`, `pendiente_cierre` y `cerrado`.

2. **Listado de Operaciones por Tipo**: `GET /api/turnos-islero/{id}/operaciones/{tipo}`
   - `{tipo}` permite: `combustible`, `lubricantes`, `creditos`, `abonos`.
   - Devuelve la lista detallada de operaciones individuales utilizando los recursos existentes (`VentaResource` y `AbonoCarteraResource`).
   - Permitido para turnos en estado `abierto`, `pendiente_cierre` y `cerrado`.

3. **Restricciones de Estado**:
   - `abierto`: permite consulta, edición y anulación de operaciones.
   - `pendiente_cierre`: permite consulta, edición y anulación completa de operaciones para revisión administrativa.
   - `cerrado`: permite consulta de operaciones como histórico / solo lectura. Bloqueado para edición o anulación.

4. **Anulación de Ventas**:
   - `POST /api/ventas/{id}/anular` (`VentaService::anular`). Valida que el turno no esté cerrado (`abierto` y `pendiente_cierre` permitidos), revierte stock y crédito, y recalcula totales del turno (`recalcularTotalesTurno`).

5. **Edición de Ventas POS y Crédito**:
   - `PUT /api/ventas/{id}` (`VentaService::update`). Valida que el turno no esté cerrado, revierte y reajusta inventarios, procesa y re-sincroniza los pagos de la venta (`pagos_venta`, `total_pagado`, `saldo_pendiente`, `estado_pago`), rechaza ediciones con pagos superiores al nuevo total (HTTP 422), calcula la diferencia de saldo pendiente (`$diferencia = $newSaldoPendiente - $oldSaldoPendiente`) y actualiza `cliente.saldo_credito`. Bloquea la edición si la venta a crédito ya tiene abonos aplicados y recalcula los totales del turno (`recalcularTotalesTurno`).

6. **Anulación de Abonos de Cartera**:
   - `POST /api/cartera/abonos/{id}/anular` (`CarteraService::anularAbono`). Valida que el turno no esté cerrado, revierte aplicaciones FIFO sobre ventas y saldos iniciales, restaura saldo a crédito del cliente, marca `estado = 'anulado'` y recalcula totales del turno (`recalcularTotalesTurno`).

7. **Edición de Abonos de Cartera**:
   - `PUT /api/cartera/abonos/{id}` (`CarteraService::updateAbono`). Valida que el turno no esté cerrado, revierte aplicaciones anteriores, actualiza valor/datos del abono, re-aplica en FIFO y recalcula totales del turno (`recalcularTotalesTurno`).

8. **Implementación Frontend (COMPLETADA)**:
   - **Servicios API**:
     - `shiftService.js`: métodos `getShiftOperations(id)` y `getShiftOperationsByType(id, tipo)`.
     - `salesService.js`: método `updateSale(id, payload)`.
     - `portfolioService.js`: métodos `updateAbono(id, data)` y `anularAbono(id, motivo)`.
   - **Componente `ShiftOperationsSection`**:
     - Creado en `front/src/features/shifts/components/ShiftOperationsSection.jsx`.
     - Muestra 4 tarjetas de resumen por categoría (Combustible, Lubricantes, Créditos, Abonos) con conteos y montos acumulados.
     - Permite navegar por pestañas/categorías para listar operaciones individuales mediante `GET /api/turnos-islero/{id}/operaciones/{tipo}`.
     - Aplica restricciones de negocio: `combustible` se presenta en modo SOLO LECTURA (sin botones de edición/anulación). En turnos en estado `cerrado`, todas las categorías quedan bloqueadas en SOLO LECTURA. En turnos `abierto` o `pendiente_cierre`, permite editar y anular ventas POS/crédito y abonos.
     - Modales integrados:
       - Modal de Edición de Ventas (`salesService.updateSale`).
       - Modal de Anulación de Ventas (`salesService.anularSale`).
       - Modal de Edición de Abonos (`portfolioService.updateAbono`).
       - Modal de Anulación de Abonos (`portfolioService.anularAbono`).
     - Re-calcula y actualiza los totales del turno y listados en tiempo real sin recargar la página del navegador.
   - **Integración en Vistas UI**:
     - `ShiftApprovalsPage.jsx`: Integrado en el panel de revisión del turno pendiente de aprobación por el administrador, refrescando balances esperados/reportados tras cada modificación.
     - `ShiftReadingsSection.jsx`: Integrado en el detalle/auditoría del turno.
     - `ShiftSummaryPage.jsx`: Integrado en el resumen de control del turno.

## 14. Correcciones Integrales de Interfaz Frontend (COMPLETADO)

Se realizaron 7 ajustes específicos en los componentes del frontend React:

1. **ShiftSummaryPage - Cálculo de Total Esperado y Tarjetas de Resumen**:
   - Ajustada la fórmula de recaudo esperado a: `(totalCombustible + totalLubricantes) - totalCreditos + totalAbonos`.
   - Incorporadas StatCards con `totalCombustible`, `totalLubricantes`, `totalCreditos`, `totalAbonos`, `totalEsperadoCaja` y `totalReportadoCaja`.

2. **ShiftSummaryPage - Sección Ajustes y Observaciones**:
   - Agregada visualización e inputs para `Otros Movimientos` (moneda), `Detalle Movimientos` (texto) u `Observación Cierre` (área de texto).

3. **ShiftEditClosingPage - Formato de Lecturas al Perder Foco (onBlur)**:
   - Se mantiene el string crudo en `lecturaFinalInput` mientras el usuario escribe para permitir la edición dígito a dígito de lecturas sin formatear en cada pulsación de tecla.
   - El formateo decimal y parseo numérico se aplica únicamente al dispararse el evento `onBlur`.

4. **ShiftApprovalsPage - Aislamiento de Formularios de Edición/Anulación**:
   - Se movió `<ShiftOperationsSection>` fuera del formulario principal de aprobación `<form onSubmit={handleAprobar}>` y se agregaron `e.stopPropagation()` / `e.preventDefault()` en las acciones de los modales de operaciones para evitar la auto-aprobación y cierre no deseado del turno al interactuar con las operaciones.

5. **IsleroTurnoDetalleView - Detalle Completo en Modo Solo Lectura**:
   - Actualizado para presentar la misma estructura completa de `ShiftSummaryPage` (StatCards de resumen, tabla de mangueras/lecturas, productos, abonos, ajustes y observaciones, esperado/reportado/balance, y `ShiftOperationsSection`) garantizando un comportamiento estrictamente de SOLO LECTURA para el perfil Islero.

6. **IsleroTurnoDetalleView - Tolerancia de $100 en Balance**:
   - Aplicada la regla de negocio donde si `Math.abs(balance) < 100`, el saldo se evalúa a `$0` para omitir variaciones insignificantes.

7. **CashSessionPage - Carga Bajo Demanda de Movimientos de Caja**:
   - Se eliminó la consulta automática de movimientos al cargar la vista si no hay una caja seleccionada (`selectedCajaId === null`). Muestra el mensaje `"Seleccione una caja para ver los movimientos"` y consulta los movimientos únicamente cuando el usuario hace clic explícito sobre la tarjeta de una caja.

## 15. Correcciones Específicas de Turnos, Búsqueda de Clientes y Anulación (COMPLETADO)

1. **IsleroTurnoDetalleView - Créditos y Abonos de Cartera del Turno**:
   - Se incorporó la consulta y despliegue de las listas detalladas de Ventas a Crédito y Abonos de Cartera del turno utilizando `shiftService.getShiftOperationsByType`.
   - Se mantiene la interfaz estrictamente en modo SOLO LECTURA sin acciones de modificación.

2. **IsleroTurnoDetalleView - Regla de Tolerancia de $100 sin alterar Esperado/Reportado**:
   - Se corrigió el cálculo del balance (`rawBalance = totalReportado - totalEsperado; balance = Math.abs(rawBalance) < 100 ? 0 : rawBalance`).
   - Se conservan intactos los valores originales de `totalEsperado` y `totalReportado`.

3. **Búsqueda de Clientes para Abonos de Cartera (API Backend & SearchClientModal)**:
   - `ClienteRepository.php`: Se reemplazó el operador específico de PostgreSQL (`ilike`) por consultas compatibles `like`, agregando soporte para búsqueda parcial sin distinguir mayúsculas/minúsculas y por múltiples palabras clave sobre `nombre`, `apellidos`, `documento` y nombre completo concatenado.
   - `SearchClientModal.jsx`: Se actualizó para permitir búsqueda por nombre o documento y presentar el listado interactivo de coincidencia múltiple de clientes.

4. **VentaService - Corrección de Variable `$esIslero` en `anular()`**:
   - `VentaService.php`: Se declaró la variable `$esIslero` (`$esIslero = !empty($venta->turno_islero_id) || ($usuarioVenta && $usuarioVenta->hasRole('islero'));`) antes de su evaluación en la anulación de ventas, garantizando un valor booleano válido y preservando la lógica de anulación y postergación de movimientos de caja.

## 16. Correcciones en Edición de Ventas POS (Caja, Recaudo e Inventarios) (IMPLEMENTADO)

1. **Sincronización de Movimientos de Caja en `VentaService::update()`**:
   - `VentaService.php` y `VentaRepository.php`: Al editar ventas POS realizadas por usuarios NO ISLEROS (`!$esIslero`), el sistema elimina los movimientos de caja anteriores asociados a la venta (`deleteMovimientosCajaByVenta`) y recrea los registros en `movimientos_caja` para la distribución de pagos finales.
   - Las ventas asociadas a turnos de isleros continúan sin movimientos de caja inmediatos (se consolidan únicamente al cierre del turno).

2. **Validación de Destino de Recaudo en `VentaService::update()`**:
   - `VentaService.php`: Se extendió a la edición la regla existente de creación: todos los productos de la venta deben pertenecer a categorías con el mismo `destino_recaudo_id`. Si se envían productos con destinos diferentes, se rechaza la edición con error 422.

3. **Protección Contra Actualización Silenciosa de Inventario**:
   - `VentaRepository.php`: `decrementInventario` e `incrementInventario` verifican la existencia previa de la tupla `(producto_id, bodega_id)` en `inventarios`. Si el registro no existe o el Query no afecta filas (`affected === 0`), lanzan una `HttpException(422)` obligando el rollback atómico de la transacción DB.

### OBJETIVO FUTURO / PENDIENTE:
- Reglas y manejo de saldo a favor del cliente en sobrepaso de deuda durante la edición de abonos.

## 17. Preparación de Base de Datos y Modelo para Abonos de Cartera (FASE 1 - IMPLEMENTADO)

1. **Migración Creada**:
   - `database/migrations/2026_09_13_120800_add_aplicacion_fields_to_abonos_cartera_table.php`: Agrega las columnas nullable `fecha_aplicacion` (`dateTime`) y `user_aplicacion_id` (`foreignId` a `users` con `nullOnDelete`). La migración ha sido creada y **no ha sido ejecutada**.

2. **Actualización del Modelo `AbonoCartera.php`**:
   - Campos agregados a `$fillable`: `'fecha_aplicacion'`, `'user_aplicacion_id'`.
   - Cast agregado a `$casts`: `'fecha_aplicacion' => 'datetime'`.
   - Relación agregada: `userAplicacion()` (`BelongsTo` hacia `User`, FK `user_aplicacion_id`).

3. **Verificación de Columna `estado`**:
   - Se confirmó que la columna `estado` en `abonos_cartera` es de tipo `string(20)` sin restricción enum/check a nivel DB, permitiendo los valores `'pendiente'`, `'aplicado'` y `'anulado'` sin requerir modificaciones en la estructura existente.

4. **Alcance y Limitación**:
   - La FASE 1 únicamente prepara la estructura de base de datos y el modelo Eloquent. NO se modificó la lógica de negocio en `CarteraService` ni `TurnoIsleroService`, ni el frontend.

## 18. Diferimiento de Abonos de Cartera para Isleros en CarteraService (FASE 2 - IMPLEMENTADO)

1. **Abonos de Isleros con Turno Abierto (`CarteraService::registrarAbono`)**:
   - Al registrar un abono de un islero con turno abierto, el registro se guarda en `abonos_cartera` con `estado = 'pendiente'`.
   - **NO** modifica `cliente.saldo_credito`.
   - **NO** modifica `ventas.saldo_pendiente` ni `ventas.estado_pago`.
   - **NO** crea registros en `abonos_cartera_detalle` ni `aplicacion_abono_saldo_inicial`.
   - **NO** crea registros en `movimientos_cartera`.
   - **NO** crea registros en `movimientos_caja`.
   - El abono queda registrado y asociado a `turno_islero_id`, recalculando `total_abonos` del turno para el arqueo económico.

2. **Abonos de Usuarios No-Isleros (`CarteraService::registrarAbono`)**:
   - Conservan su flujo inmediato actual: se guardan con `estado = 'aplicado'`, ejecutan FIFO, actualizan `cliente.saldo_credito`, generan `abonos_cartera_detalle`, `movimientos_cartera` e inmediato `MovimientoCaja`.

3. **Anulación y Edición de Abonos Pendientes**:
   - `anularAbono()`: Si el abono está `pendiente`, actualiza su estado a `anulado` y recalcula los totales del turno sin realizar reversiones de detalles o crédito de cliente. Si está `aplicado`, ejecuta la reversión profunda previa.
   - `updateAbono()`: Si el abono está `pendiente`, actualiza los campos directos (`valor`, `medio_pago`, `observacion`, `cliente_id`) y recalcula los totales del turno sin aplicar ni modificar detalles o crédito de cliente.

4. **Soporte de Consultas en Repositorio (`TurnoIsleroRepository`)**:
   - Se ajustaron `sumAbonosByTurno()`, `sumAbonosByTurnoAndMetodo()`, `getAbonosDetalleByTurno()`, `getAbonosCarteraByTurno()` y `getResumenOperacionesByTurno()` para consultar `whereIn('estado', ['registrado', 'pendiente', 'aplicado'])`, asegurando la compatibilidad de totales de abonos pendientes.

398: ### ALCANCE Y PENDIENTES:
399: - Saldo a favor automático en sobrepaso de deuda durante la edición de abonos.
400: 
401: ## 19. Aplicación Definitiva de Abonos Pendientes en Cierre de Turno (FASE 3 - IMPLEMENTADO)
402: 
403: 1. **Procesamiento de Abonos Pendientes (`TurnoIsleroService::cerrar` & `CarteraService::procesarAbonosPendientesTurno`)**:
404:    - Durante la ejecución del cierre definitivo del turno (`TurnoIsleroService::cerrar()`), se consultan secuencialmente todos los abonos del turno con `estado = 'pendiente'`, ordenados por `id` ascendente.
405:    - Para cada abono pendiente:
406:      - Se verifica que la cartera/deuda pendiente activa disponible del cliente sea igual o mayor que el valor del abono.
407:      - **Regla Estricta de Aplicación Completa (Sin Saldo a Favor)**: Si la deuda disponible del cliente es menor que el valor del abono, el sistema lanza una `HttpException(422)` abortando la ejecución y ejecutando **ROLLBACK completo** de toda la transacción del cierre (lecturas, AJT, cartera, caja, estado del turno).
408:      - Si la deuda es suficiente, se ejecuta la distribución FIFO sobre ventas y saldos iniciales pendientes.
409:      - Se actualiza `saldo_pendiente` y `estado_pago` en `ventas` / `saldos_iniciales_cartera`.
410:      - Se crean los registros correspondientes en `abonos_cartera_detalle` y `aplicacion_abono_saldo_inicial`.
411:      - Se actualiza `cliente.saldo_credito`.
412:      - Se crea el registro en `movimientos_cartera` (`tipo_movimiento = 'abono'`).
413:      - Se actualizan en `abonos_cartera`: `estado = 'aplicado'`, `fecha_aplicacion = now()`, `user_aplicacion_id = $userId`.
414: 
415: 2. **Generación Consolidada de Caja**:
416:    - En el cierre definitivo, `TurnoIsleroService::registrarMovimientosCajaAbonos()` registra los movimientos de caja de ingreso por abono cartera para cada abono procesado, omitiendo duplicados.
417: 
418: 3. **Idempotencia y Atomicidad**:
419:    - Todo el proceso corre dentro de un único bloque `DB::transaction()`.
420:    - Los abonos en estado `'aplicado'`, `'registrado'` o `'anulado'` son omitidos de re-procesamientos.
421: 
422: ## 20. Interfaz de Edición de Ventas Tipo Carrito en `ShiftOperationsSection` (IMPLEMENTADO)
423: 
424: 1. **Nueva Experiencia Frontend de Edición de Ventas POS**:
425:    - Se reemplazó el formulario simple de edición de ventas dentro de `ShiftOperationsSection.jsx` por una interfaz interactiva de tipo carrito, tomando como referencia la experiencia de `LubricantSalesPage.jsx`.
### ALCANCE Y PENDIENTES:
- Saldo a favor automático en sobrepaso de deuda durante la edición de abonos.

## 19. Aplicación Definitiva de Abonos Pendientes en Cierre de Turno (FASE 3 - IMPLEMENTADO)

1. **Procesamiento de Abonos Pendientes (`TurnoIsleroService::cerrar` & `CarteraService::procesarAbonosPendientesTurno`)**:
   - Durante la ejecución del cierre definitivo del turno (`TurnoIsleroService::cerrar()`), se consultan secuencialmente todos los abonos del turno con `estado = 'pendiente'`, ordenados por `id` ascendente.
   - Para cada abono pendiente:
     - Se verifica que la cartera/deuda pendiente activa disponible del cliente sea igual o mayor que el valor del abono.
     - **Regla Estricta de Aplicación Completa (Sin Saldo a Favor)**: Si la deuda disponible del cliente es menor que el valor del abono, el sistema lanza una `HttpException(422)` abortando la ejecución y ejecutando **ROLLBACK completo** de toda la transacción del cierre (lecturas, AJT, cartera, caja, estado del turno).
     - Si la deuda es suficiente, se ejecuta la distribución FIFO sobre ventas y saldos iniciales pendientes.
     - Se actualiza `saldo_pendiente` y `estado_pago` en `ventas` / `saldos_iniciales_cartera`.
     - Se crean los registros correspondientes en `abonos_cartera_detalle` y `aplicacion_abono_saldo_inicial`.
     - Se actualiza `cliente.saldo_credito`.
     - Se crea el registro en `movimientos_cartera` (`tipo_movimiento = 'abono'`).
     - Se actualizan en `abonos_cartera`: `estado = 'aplicado'`, `fecha_aplicacion = now()`, `user_aplicacion_id = $userId`.

2. **Generación Consolidada de Caja**:
   - En el cierre definitivo, `TurnoIsleroService::registrarMovimientosCajaAbonos()` registra los movimientos de caja de ingreso por abono cartera para cada abono procesado, omitiendo duplicados.

3. **Idempotencia y Atomicidad**:
   - Todo el proceso corre dentro de un único bloque `DB::transaction()`.
   - Los abonos en estado `'aplicado'`, `'registrado'` o `'anulado'` son omitidos de re-procesamientos.

## 20. Interfaz de Edición de Ventas Tipo Carrito en `ShiftOperationsSection` (IMPLEMENTADO)

1. **Nueva Experiencia Frontend de Edición de Ventas POS**:
   - Se reemplazó el formulario simple de edición de ventas dentro de `ShiftOperationsSection.jsx` por una interfaz interactiva de tipo carrito, tomando como referencia la experiencia de `LubricantSalesPage.jsx`.
   - **Buscador y Catálogo de Productos**: Permite buscar y agregar productos activos (excluyendo combustibles) desde el catálogo mediante `productService.getProducts()`.
   - **Lista de Ítems / Carrito**: Cada ítem en el carrito muestra nombre del producto, precio unitario **informativo y en solo lectura**, controles de incremento/decremento `[-] cantidad [+]` o edición directa de cantidad, subtotal de línea (`cantidad * precio_unitario`), y botón de eliminación (`Trash2`).
   - **Recálculo de Totales**: El total general de la venta se calcula dinámicamente como la suma de los subtotales de cada línea del carrito.
   - **Medio de Pago y Datos de Venta**: Soporta seleccionar el medio de pago/destino de recaudo (Efectivo, QR, Datáfono, Transferencia, Consignación) para ventas a contado, tipo de venta (contado/crédito), selección de cliente y observación.

2. **Validación y Envío**:
   - Valida que el carrito contenga al menos 1 producto y que todas las cantidades sean mayores a 0.
   - Si la venta es a crédito, valida obligatoriamente la selección de cliente.
   - Mantiene la compatibilidad estricta enviando los payloads a `salesService.updateSale(id, payload)` sin alterar rutas ni contratos backend.
   - Al guardar con éxito, cierra el modal y ejecuta `reloadData()` para refrescar en tiempo real el listado de operaciones del turno y los totales del resumen.
   - Las ventas de combustibles y operaciones de turnos cerrados se mantienen en **Solo Lectura**.

3. **Filtrado de Catálogo por Bodega del Turno Islero**:
   - `ProductoController::index` admite el parámetro opcional `bodega_id` (`GET /api/productos?per_page=30&bodega_id={id}`). Si se omite, mantiene la bodega del usuario autenticado como valor por defecto para no afectar `LubricantSalesPage`.
   - `TurnoIsleroService::obtenerResumenOperaciones`, `TurnoIsleroResource` y `VentaResource` exponen explícitamente `bodega_id` y los detalles de la bodega del turno islero (derivada de `$turno->usuario->bodega_id`).
   - En `ShiftOperationsSection.jsx`, al abrir el modal de edición de venta POS, se obtiene la `bodega_id` del turno y se pasa a `loadModalProducts(query, shiftBodegaId)`, garantizando que un administrador en revisión consulte el inventario y catálogo de la bodega del turno editado y no de su usuario propio.

## 21. Corrección de Recaudo Automático de Lubricantes en `ShiftApprovalsPage` (IMPLEMENTADO)

1. **Diagnóstico del Desfase de Caja en Revisiones de Cierre**:
   - Al cargar la revisión del turno en `ShiftApprovalsPage.jsx`, el backend (`TurnoIsleroService::revisionCierre`) calcula dinámicamente en `destinos_recaudo` las ventas confirmadas de lubricantes (excluyendo ventas anuladas).
   - Sin embargo, `ShiftApprovalsPage` sobreescribía `initialPagos` con la declaración manual preexistente del islero (`data.turno.recaudos`) para todos los destinos si existía un registro en `recaudos`.
   - Dado que Lubricantes es un destino automático y de solo lectura (`readOnly`/`disabled`), la interfaz mostraba valores desactualizados declarados por el islero (ej. $25.000) en lugar del recaudo real calculado por el sistema a partir de las ventas POS confirmadas (ej. $50.000).

2. **Ajuste Aplicado**:
   - Se modificó `ShiftApprovalsPage.jsx` en la inicialización de `setEditDestinosRecaudo` para que los destinos automáticos (`d.nombre === "Lubricantes"`) nunca sean sobreescritos por `recaudoIslero`.
   - Los destinos automáticos ahora utilizan siempre `d.pagos` (el cálculo actualizado del backend basado únicamente en ventas confirmadas), reflejando correctamente los montos ($50.000) tanto en la tarjeta de recaudo como en el cálculo de `totalReportado` y `balance` del cierre.

3. **Ubicación del Botón de Aprobación**:
   - En `ShiftApprovalsPage.jsx`, se reubicó el botón "Guardar Cambios y Aprobar Turno" al final de todo el flujo de revisión, posicionándolo inmediatamente después de la sección `ShiftOperationsSection` ("OPERACIONES DEL TURNO").
   - El botón mantiene intacta su función `handleAprobar` y su enlace nativo mediante `form="approvals-form"`.

## 22. Edición de Abonos de Cartera con Selección de Caja y Formato Monetario al Blur (IMPLEMENTADO)

1. **Selección de Caja / Destino de Recaudo**:
   - En `ShiftOperationsSection.jsx`, el modal de edición de abonos consulta las cajas abiertas disponibles llamando a `cashService.getCurrentCash()` (`GET /api/caja/actual`) e incluye la caja actual asignada al abono en las opciones del selector `<select>`.
   - El valor seleccionado de `caja_id` se envía en el payload a `portfolioService.updateAbono(id, payload)` y se valida en `UpdateAbonoCarteraRequest` (`caja_id => exists:cajas,id`).
   - `CarteraService::updateAbono` actualiza `abonos_cartera.caja_id`.

2. **Formato Monetario al Perder Foco (`blur`) & Parseo Correcto**:
   - Durante la digitación de `valor`, el usuario ingresa números y coma libremente sin interrupción carácter por carácter.
   - En el evento `blur`, se aplica formato de moneda colombiana `es-CO` con separador de miles por punto y decimales por coma (`100000,00` -> `100.000,00`, `1000000` -> `1.000.000,00`, `153400,76` -> `153.400,76`).
   - Se ajustó `parseAbonoValor` para diferenciar cadenas numéricas/floats estándar recibidas de la API (ej. `"150000.00"` o `"153400.76"`) de cadenas formateadas con coma en la UI (ej. `"153.400,76"`), evitando que la eliminación incondicional de puntos convirtiera montos como `150.000,00` en `15.000.000,00` (factor x100).
   - El estado numérico subyacente (`valor`) mantiene el tipo de dato flotante/numérico JS puro (ej. `153400.76` / `150000`) enviado al backend.

3. **Sincronización de Movimientos de Caja**:
   - En `CarteraService::updateAbono`, si el abono posee un movimiento de caja asociado (`origen_modulo = 'cartera'`, `origen_id = $abono->id`, `categoria_movimiento = 'abono_cartera'`), este se actualiza con la nueva `caja_id`, `monto` y `medio_pago` sin generar registros duplicados.
   - Si no existía un movimiento de caja y se asigna una caja a un abono independiente sin turno, se crea el movimiento correspondiente en la caja indicada.


