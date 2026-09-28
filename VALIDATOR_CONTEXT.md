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

## 23. Control de Operaciones de Turno mediante Permisos Spatie (IMPLEMENTADO)

1. **Permisos y Seeder**:
   - Permisos específicos: `vender_combustible`, `vender_lubricantes`, `registrar_abonos_cartera`.
   - Se asignaron a los roles Spatie:
     - `admin`: todos los permisos.
     - `islero`: `ver_turnos_islero`, `abrir_turnos_islero`, `cerrar_turnos_islero`, `vender_combustible`, `vender_lubricantes`, `crear_ventas`, `registrar_abonos_cartera`.
     - `vendedor` y `cajero`: `ver_turnos_islero`, `abrir_turnos_islero`, `cerrar_turnos_islero`, `vender_lubricantes`, `crear_ventas`, `registrar_abonos_cartera`.

2. **Apertura de Turnos según Permisos (Backend & Frontend)**:
   - `AbrirTurnoIsleroRequest.php`: la validación de `mangueras` es obligatoria únicamente si el usuario posee `vender_combustible`. Si no posee `vender_combustible`, la regla de mangueras se evalúa como `nullable`.
   - `TurnoIsleroService::abrir`: si el usuario posee `vender_combustible`, exige mangueras activas y genera sus lecturas iniciales. Si no posee `vender_combustible`, permite omitir mangueras sin registrar lecturas iniciales.
   - `OpenShiftModal.jsx`: consulta los permisos del usuario con `usePermissions()`. Si no tiene `vender_combustible`, oculta la selección de mangueras.

3. **Cierre de Turnos sin Lecturas de Combustible**:
   - `TurnoIsleroService::solicitarCierre`, `revisionCierre` y `cerrar`: si el turno no posee mangueras/lecturas (`$turno->lecturas->isEmpty()`), se omiten las validaciones de lecturas finales, los cálculos de galones físicos y las ventas sintéticas AJT.
   - `ShiftClosingPage.jsx`: renderiza la sección de lecturas finales de mangueras estrictamente cuando el usuario autenticado posee `vender_combustible` y el turno contiene mangueras asignadas.

4. **Control Estricto de Visibilidad en Interfaz Operativa**:
   - `ShiftOperationsSection.jsx`: evalúa estrictamente `vender_combustible`, `vender_lubricantes` y `registrar_abonos_cartera` sin fallbacks permisivos (`|| ver_turnos_islero` o `|| crear_ventas`). Las 4 tarjetas de resumen se renderizan condicionalmente según los permisos específicos del usuario.
   - `ShiftSummaryPage.jsx`: la sección "Detalle de Mangueras" se renderiza condicionalmente sólo si el usuario autenticado tiene el permiso `vender_combustible` y el turno contiene lecturas.

5. **Resolución de Caché de Permisos (`usePermissions.js` & `authService.js`)**:
   - `usePermissions.js`: ejecuta una consulta asíncrona a `GET /api/auth/me/permissions` al montar el componente en `useEffect(..., [])`, garantizando un único llamado sin bucles infinitos y refrescando los permisos Spatie actualizados desde el backend. Soporta también arreglos de permisos mediante `.some()`.
   - `authService.js`: al iniciar sesión, elimina de `localStorage` la clave `permissions` obsoleta para forzar que el nuevo usuario obtenga sus permisos reales directamente del servidor.

6. **Integración Completa de Permisos en Shift Management, Menú Principal y Shift Closing**:
   - `ShiftManagementPage.jsx`: evalúa `vender_combustible`, `vender_lubricantes` y `registrar_abonos_cartera` con `usePermissions()`. Oculta individualmente los botones de acción rápida ("Venta Combustible", "Venta Lubricantes", "Registrar Abonos") cuando el usuario no cuenta con el permiso correspondiente.
   - `Sidebar.jsx`: protege las entradas de navegación "Ventas Combustible" (`vender_combustible`), "Ventas Lubricantes" (`vender_lubricantes`) y "Cartera" (`['ver_cartera', 'registrar_abonos_cartera']`).
   - `ShiftClosingPage.jsx`: renderiza la sección de mangueras únicamente cuando `hasPermission("vender_combustible") && Array.isArray(summary?.lecturas) && summary.lecturas.length > 0`, permitiendo solicitar cierres limpios sin requerir lecturas cuando el usuario carece de permiso o el turno no tiene mangueras.

7. **Filtrado de Destinos de Recaudo en Backend según Permisos del Usuario del Turno**:
   - `TurnoIsleroService.php`: se implementó el método privado `esDestinoPermitidoParaUsuario($destino, $user)` para filtrar destinos según los permisos del usuario propietario del turno (`$turno->usuario`):
     - `codigo === 'COMB'`: requiere que el usuario tenga el permiso Spatie `vender_combustible`.
     - `codigo === 'LUBR'`: requiere que el usuario tenga el permiso Spatie `vender_lubricantes`.
     - Códigos diferentes de `COMB` y `LUBR`: se mantienen sin alteración.
   - Se aplicó este filtro tanto en `resumenCierre()` (para que `destinos_recaudo` solo devuelva los destinos permitidos al usuario) como en `validarRecaudoDestinos()` (para que el backend exija en la validación únicamente los destinos pertenecientes a las capacidades del usuario, evitando errores `422` al solicitar/aprobar el cierre).
   - No se modificó el módulo ni repositorio de destinos/cajas, manteniendo intactas la estructura de base de datos y la relación destino -> caja.

8. **Ajuste de Validación de `lecturas_finales` en Solicitar Cierre (`SolicitarCierreTurnoIsleroRequest.php`)**:
   - `SolicitarCierreTurnoIsleroRequest.php`: evalúa si el usuario propietario del turno (`$turno->usuario ?? User::find($turno->user_id)`) posee el permiso Spatie `vender_combustible`.
   - Si el propietario posee `vender_combustible`: `lecturas_finales` se mantiene estrictamente obligatorio (`['required', 'array', 'min:1']`).
   - Si el propietario NO posee `vender_combustible`: `lecturas_finales` permite un arreglo vacío (`['nullable', 'array']`), permitiendo el envío de `"lecturas_finales": []` sin lanzar el error `"The lecturas finales field is required."`.
   - Las reglas hijas (`lecturas_finales.*.manguera_id`, `lecturas_finales.*.lectura_final`) utilizan `required_with:lecturas_finales`, garantizando que si el payload contiene elementos de mangueras, estos se validen estrictamente, pero si el arreglo está vacío, la validación apruebe limpiamente.
   - No se alteró la lógica de negocio del cierre ni otros endpoints.

9. **Filtrado de Panel Administrativo de Revisión de Turnos (`revisionCierre` & `ShiftApprovalsPage.jsx`)**:
   - `TurnoIsleroService::revisionCierre()`: reutiliza el helper `esDestinoPermitidoParaUsuario($destino, $usuarioTurno)` para filtrar `$destinosCaja` según los permisos del usuario propietario del turno (`$turno->usuario`). Si el propietario carece de `vender_combustible`, se devuelve `$lecturas = collect([])` y solo se retornan los destinos autorizados (ej. `LUBR`).
   - `ShiftApprovalsPage.jsx`: condiciona la renderización de la tarjeta visual de "Mangueras y Lecturas" a `editLecturas.length > 0`, y adapta el contenedor a 1 sola columna centrada (`space-y-6 max-w-2xl mx-auto`) cuando el turno pertenece a un usuario de solo lubricantes, ocultando la sección de combustible por completo al administrador sin perder su capacidad de revisar y aprobar el turno.

10. **Ajuste de Validación de `lecturas_finales` en Endpoint de Cierre Directo/Aprobación (`CerrarTurnoIsleroRequest.php`)**:
    - `CerrarTurnoIsleroRequest.php`: evalúa si el usuario propietario del turno (`$turno->usuario ?? User::find($turno->user_id)`) posee el permiso Spatie `vender_combustible`.
    - Si el propietario posee `vender_combustible`: `lecturas_finales` se mantiene estrictamente obligatorio (`['required', 'array', 'min:1']`).
    - Si el propietario NO posee `vender_combustible`: `lecturas_finales` permite un arreglo vacío (`['nullable', 'array']`), permitiendo al administrador aprobar o cerrar directamente el turno enviando `"lecturas_finales": []` sin lanzar el error `"The lecturas finales field is required."`.
    - Las reglas hijas (`lecturas_finales.*.manguera_id`, `lecturas_finales.*.lectura_final`) utilizan `required_with:lecturas_finales`, garantizando que si el payload contiene elementos de mangueras, estos se validen strictly, pero si el arreglo está vacío, la validación apruebe limpiamente.
    - No se alteró la lógica de negocio del cierre ni otros endpoints.

11. **Postposición de Movimientos de Caja al Cierre Definitivo para Ventas bajo Turno Abierto (`VentaService.php`)**:
    - Se eliminaron las verificaciones obsoletas basadas en roles Spatie (`hasRole('islero')`) en `VentaService.php` (`create`, `anular`, `createCombustible`, `update`).
    - `create()` y `createCombustible()`: evalúan `empty($turnoAbierto)`. Si la venta se realiza bajo un turno abierto (sin importar el rol del usuario: islero, vendedor, cajero, supervisor, etc.), la generación inmediata de `movimientos_caja` se omite y se pospone para el cierre definitivo del turno (`TurnoIsleroService::cerrar()`). Si no hay turno abierto, se crea el movimiento de caja de manera inmediata.
    - `anular()` y `update()`: evalúan `empty($venta->turno_islero_id)`. Los movimientos de caja de anulación o sincronización por edición únicamente se ejecutan si la venta fue realizada fuera de un turno.
    - Se conserva la trazabilidad y la asignación de `caja_id` en `pagos_venta` al momento de la venta.
    - `TurnoIsleroService::cerrar()` consolida en el cierre los movimientos de caja para todas las ventas del turno mediante `registrarMovimientosCaja()`, garantizando cero duplicados.

12. **Generación Incondicional de Movimientos de Caja en Cierre Administrativo (`TurnoIsleroService.php`)**:
    - `TurnoIsleroService::cerrar()`: Se eliminó la guarda obsoleta `if ($turno->usuario->hasRole('islero'))` alrededor de `registrarMovimientosCaja()` y `registrarMovimientosCajaAbonos()`.
    - Al ejecutar el cierre definitivo de un turno (`PENDIENTE_CIERRE` -> `CERRADO`), se invocan de forma incondicional `registrarMovimientosCaja($dto, $turno)` y `registrarMovimientosCajaAbonos($turno, $dto->user_id)`.
    - Garantiza que las ventas y abonos de cualquier usuario (islero, vendedor, cajero, supervisor) con turno abierto generen sus correspondientes `movimientos_caja` de ingreso al momento del cierre definitivo.
    - Preserva la idempotencia en `registrarMovimientosCaja` (vía `findMovimientoCajaByOrigenMedioPagoAndDestino`) y `registrarMovimientosCajaAbonos` (vía `findMovimientoCajaByOrigenAndOrigenId`), evitando duplicación de movimientos de caja.
    - Mantiene la separación conceptual: `$turno->user_id` / `$turno->usuario` es el propietario/operador del turno, mientras que `$dto->user_id` registra al usuario administrador autenticado que ejecuta el cierre definitivo.

## 24. Fase 1 — Datos Fiscales de `ConfiguracionEmpresa` (IMPLEMENTADO)

1. **Campos Fiscales Agregados a `configuracion_empresa`**:
   - Migración: `2026_09_16_000001_add_datos_fiscales_to_configuracion_empresa_table.php` (no destructiva, todas las columnas `nullable`).
   - `tipo_persona`: String(20), nullable ('juridica' / 'natural' o código DIAN '1' / '2').
   - `tipo_documento`: String(20), nullable ('NIT', '31').
   - `tipo_regimen`: String(50), nullable ('48' Responsable de IVA, '49' No responsable).
   - `responsabilidades_fiscales`: JSON, nullable (`array` en Eloquent, ej. `["O-13", "O-47"]`).
   - `codigo_postal`: String(10), nullable.
   - `matricula_mercantil`: String(50), nullable.

2. **Actualización de Capas de `ConfiguracionEmpresa`**:
   - `ConfiguracionEmpresa.php`: Se añadieron los 6 campos a `$fillable` y `'responsabilidades_fiscales' => 'array'` a `$casts`.
   - `UpdateConfiguracionEmpresaDTO.php`: Se añadieron los 6 campos como propiedades opcionales.
   - `UpdateConfiguracionEmpresaRequest.php`: Se añadieron reglas de validación `nullable` para los 6 campos.
   - `ConfiguracionEmpresaMapper.php`: Mapea los 6 campos desde el array `$data` al DTO.
   - `ConfiguracionEmpresaService.php`: Incluye los 6 campos en el array persistido por el repositorio.
   - `ConfiguracionEmpresaResource.php`: Expone los 6 campos en el JSON de respuesta.

3. **Sin Acoplamiento a Proveedores ni Cambios en Lógica Comercial**:
   - No se almacenan tokens, llaves API, ni variables específicas de proveedores (MATIAS).
   - No se modificaron ventas, clientes, productos, impuestos, caja, cartera ni turnos.

### OBJETIVO FUTURO (PENDIENTE)
- Creación de esquema `configuraciones_facturacion` y `resoluciones_facturacion`.
- Selección de proveedor activo (`FacturacionManager`).
- Implementación de `FacturacionService` y `MatiasProvider`.
- Tabla `documentos_electronicos`, reintentos y prevención de duplicados.
- Emisión de notas crédito/débito, documento soporte, RADIAN y nómina electrónica.

## 25. Fase 2 — Estructura de Configuración de Facturación Electrónica (`ConfiguracionFacturacion`) (IMPLEMENTADO)

1. **Tabla y Modelo `ConfiguracionFacturacion`**:
   - Migración: `2026_09_16_000002_create_configuracion_facturacion_table.php`.
   - Tabla: `configuracion_facturacion`.
   - Relación: Pertenece a `ConfiguracionEmpresa` via `configuracion_empresa_id` (`belongsTo` / `hasOne`).
   - Campos y Valores por Defecto:
     - `proveedor_activo`: String(50), default `'matias'` (representación abstracta del proveedor).
     - `ambiente`: String(20), default `'sandbox'` (opciones: `sandbox`, `produccion`).
     - `facturacion_electronica_activa`: Boolean, default `false` (desactivada por seguridad en inicialización).
     - `reintentos_automaticos`: Boolean, default `true`.
     - `max_reintentos`: UnsignedInteger, default `3`.

2. **Módulo Completo de Arquitectura por Capas (`app/Modules/ConfiguracionFacturacion`)**:
   - `ConfiguracionFacturacion.php`: Modelo Eloquent con `$fillable` y `$casts`.
   - `UpdateConfiguracionFacturacionDTO.php`: DTO de transferencia de parámetros.
   - `ConfiguracionFacturacionRepositoryInterface.php`: Interfaz del repositorio.
   - `ConfiguracionFacturacionRepository.php`: Repositorio Eloquent.
   - `ConfiguracionFacturacionService.php`: Servicio de aplicación (`get()` e `update()`). Auto-inicializa valores seguros por defecto si no existe registro previo.
   - `ConfiguracionFacturacionMapper.php`: Mapea arrays recibidos al DTO.
   - `UpdateConfiguracionFacturacionRequest.php`: Validaciones (`ambiente` en `sandbox,produccion`, booleanos, `max_reintentos` min:1 max:10).
   - `ConfiguracionFacturacionResource.php`: Resource de formato JSON de respuesta.
   - `ConfiguracionFacturacionController.php`: Controlador con acciones `show` y `update`.
   - `AppServiceProvider.php`: Inyección de dependencias `ConfiguracionFacturacionRepositoryInterface` -> `ConfiguracionFacturacionRepository`.
   - `routes/api.php`: Rutas protegidas `GET /api/configuracion-facturacion` y `PUT /api/configuracion-facturacion`.

3. **Sin Acoplamiento Técnico ni Alteración de Lógica Comercial**:
   - No se implementó `MatiasProvider`, `FacturacionService`, `FacturacionManager`, tokens, URLs ni llaves API de MATIAS.
   - No se alteró la lógica actual de ventas, clientes, productos, impuestos, caja, cartera ni turnos.

### OBJETIVO FUTURO (PENDIENTE)
- Creación de `resoluciones_facturacion`.
- Tabla `documentos_electronicos`, jobs de cola, reintentos automáticos y prevención de duplicados.
- Emisión de notas crédito/débito, documento soporte, RADIAN y nómina electrónica.

## 26. Fase 3 — Arquitectura Interna Desacoplada de Facturación Electrónica (IMPLEMENTADO)

1. **Abstracción e Interfaces (`app/Modules/Facturacion`)**:
   - `FacturacionProviderInterface.php`: Interfaz genérica e independiente del proveedor que define `enviarFactura(SolicitudFacturaDTO $solicitud): RespuestaFacturacionDTO` y `consultarEstado(string $identificadorExterno): RespuestaFacturacionDTO`.

2. **DTOs Genéricos del ERP (`SolicitudFacturaDTO` y `RespuestaFacturacionDTO`)**:
   - `SolicitudFacturaDTO.php`: DTO genérico de solicitud comercial de factura (contiene `venta_id`, `prefijo`, `folio`, `fecha_emision`, `tipo_documento`, `cliente`, `emisor`, `items`, `totales`, `medio_pago`, `observacion`, `metadatos`) sin propiedades de ningún proveedor.
   - `RespuestaFacturacionDTO.php`: DTO genérico de respuesta normalizada (contiene `exitoso`, `estado`, `identificador_externo`, `numero_factura`, `cufe`, `qr_code`, `pdf_url`, `xml_url`, `mensaje`, `errores`, `datos_tecnicos`) con métodos estáticos `exitoso()` y `error()`.

3. **Resolución Dinámica y Desacoplada de Proveedores (`FacturacionManager`)**:
   - `FacturacionManager.php`: Factory/Manager encargado de instanciar la implementación de `FacturacionProviderInterface` según `ConfiguracionFacturacion.proveedor_activo` y `ambiente`. Soporta extensión dinámica mediante `extend()`. No realiza fallback silencioso y arroja excepciones controladas ante proveedores no soportados o no configurados.
   - Registrado como Singleton en `AppServiceProvider.php`.

4. **Servicio de Aplicación (`FacturacionService`)**:
   - `FacturacionService.php`: Capa de aplicación que consulta `ConfiguracionFacturacion`, valida el estado del servicio (`facturacion_electronica_activa`), solicita el provider a `FacturacionManager` y ejecuta las operaciones genéricas devolviendo respuestas normalizadas. Cero acoplamiento a clases concretas como `MatiasProvider` o URLs externas.

5. **Punto de Extensión `MatiasProvider`**:
   - `MatiasProvider.php`: Estructura base que implementa `FacturacionProviderInterface`. La comunicación HTTP real, autenticación, headers y payloads quedan congelados a la espera de la especificación técnica oficial de MATIAS API.

6. **Integridad Comercial y del Sistema**:
   - `VentaService`, ventas, clientes, productos, inventario, caja, cartera y turnos se mantienen **100% INTACTOS** y sin modificaciones ni llamadas externas.

### OBJETIVO FUTURO (PENDIENTE)
- Integración de `VentaService` con `FacturacionService` (conversión Venta -> `SolicitudFacturaDTO`).
- Tabla `documentos_electronicos`, Jobs/Queues, eventos y reintentos automáticos.
- RADIAN, Documento Soporte y Nómina Electrónica.

## 27. Fase 4 — Cliente HTTP e Integración Técnica con MATIAS Sandbox (IMPLEMENTADO)

1. **Cliente HTTP Técnico (`MatiasClient`)**:
   - `MatiasClient.php`: Implementa la comunicación técnica contra MATIAS Sandbox (`https://sandbox-api.matias-api.com`). Encapsula la autenticación Bearer (`Authorization: Bearer <TOKEN>`), encabezados `Accept: application/json`, `Content-Type: application/json` y el encabezado condicional de pruebas Sandbox `X-Sandbox-Force-Status`. Maneja excepciones de red, tiempo de espera (timeout) y códigos de error HTTP (400, 401, 403, 404, 422, 429, 5xx).

2. **Configuración de Servicios (`config/services.php`)**:
   - Registrada la configuración del proveedor `'matias'` desacoplada en `config/services.php`, enlazando `token => env('MATIAS_API_TOKEN')`, `sandbox_url => env('MATIAS_SANDBOX_URL', 'https://sandbox-api.matias-api.com')` y `production_url => env('MATIAS_PRODUCTION_URL')`. Se garantiza que ningún secreto se escriba directamente en el código fuente.

3. **Proveedor de Facturación (`MatiasProvider`)**:
   - `MatiasProvider.php`: Conectado con `MatiasClient` y la lectura segura del token desde `config('services.matias.token')`. Valida la presencia del token antes de proceder y delega la ejecución HTTP al cliente técnico.

4. **Integridad Comercial e Incólumidad del Sistema**:
   - `VentaService`, ventas, clientes, productos, inventario, caja, cartera, turnos y base de datos se mantienen **100% INTACTOS** y sin modificaciones ni persistencia de documentos.

### OBJETIVO FUTURO (PENDIENTE)
- Endpoint exacto documentado para la consulta de estado por `trackId`.
- URL oficial de producción de MATIAS API.
- Integración de `VentaService` con `FacturacionService`.
- Tabla `documentos_electronicos`, Jobs, Queues, eventos y reintentos automáticos.
- RADIAN, Documento Soporte y Nómina Electrónica.

## 28. Fase 5 — Integración MATIAS POST /invoice (IMPLEMENTADO)

1. **Mapper de Payload MATIAS API (`MatiasInvoiceMapper`)**:
   - `MatiasInvoiceMapper.php`: Implementa la transformación de `SolicitudFacturaDTO` al arreglo asociativo exigido por MATIAS API para `POST /invoice`. Mapea exactamente los bloques confirmados por la colección Postman: `resolution_number`, `prefix`, `notes`, `document_number`, `graphic_representation`, `send_email`, `operation_type_id`, `type_document_id`, `payments`, `document_signature`, `customer`, `lines`, `legal_monetary_totals` y `tax_totals`. Mantiene los tipos exactos de datos (strings decimales en cantidades/importes, enteros en flags e IDs, etc.).

2. **Integración Completa en Provider (`MatiasProvider`)**:
   - `MatiasProvider.php`: Método `enviarFactura()` actualizado para orquestar la transformación vía `MatiasInvoiceMapper::toMatiasPayload()`, llamar a `MatiasClient::postInvoice()` y normalizar la respuesta HTTP devuelta hacia `RespuestaFacturacionDTO` (extrayendo `cufe`, `qrCode`, `pdfUrl`, `xmlUrl`, `numeroFactura`, e `identificadorExterno`).

3. **Incolumidad Comercial e Integridad del ERP**:
   - `VentaService`, ventas, clientes, productos, inventario, caja, cartera, turnos y base de datos permanecen **100% INTACTOS** y sin modificaciones ni llamadas automáticas.

### OBJETIVO FUTURO (PENDIENTE)
- Integración de `VentaService` con `FacturacionService` (conversión automática Venta -> `SolicitudFacturaDTO`).
- Endpoint exacto documentado para la consulta de estado por `trackId`.
- Emisión de notas crédito/débito, documento soporte, RADIAN y nómina electrónica.

## 29. Fase 6 — Persistencia de Documentos Electrónicos (`documentos_electronicos`) (IMPLEMENTADO)

1. **Migración y Tabla Creada (`documentos_electronicos`)**:
   - `2026_09_16_000003_create_documentos_electronicos_table.php`: Migración ejecutada correctamente (`php artisan migrate` DONE).
   - Estructura implementada: `id`, `venta_id` (foreignKey -> `ventas`, nullable, **sin restricción UNIQUE**), `configuracion_facturacion_id` (foreignKey -> `configuracion_facturacion`, nullable), `tipo_documento` (default `'factura_electronica'`), `estado` (default `'pendiente'`), `proveedor` (default `'matias'`), `ambiente` (default `'sandbox'`), `identificador_externo`, `cufe`, `numero_documento`, `prefijo`, `track_id`, `mensaje`, `errores` (`json`), `datos_tecnicos` (`json`) y `timestamps`.
   - Índices creados: `venta_id`, `estado`, `tipo_documento`, `identificador_externo` y `cufe`.

2. **Modelo Eloquent y Relaciones (`DocumentoElectronico` y `Venta`)**:
   - `DocumentoElectronico.php`: Modelo en `app/Models/DocumentoElectronico.php` con `$fillable`, `$casts` (`'errores' => 'array'`, `'datos_tecnicos' => 'array'`) y relaciones `belongsTo` hacia `Venta` y `ConfiguracionFacturacion`.
   - `Venta.php`: Agregada la relación `documentosElectronicos()` (`hasMany` hacia `DocumentoElectronico`), permitiendo múltiples documentos electrónicos para una misma venta.

3. **Capa de Aplicación y Persistencia del Módulo (`app/Modules/Facturacion`)**:
   - DTOs: `CrearDocumentoElectronicoDTO.php` y `ActualizarDocumentoElectronicoDTO.php`.
   - Repositorio e Interfaz: `DocumentoElectronicoRepositoryInterface.php` y `DocumentoElectronicoRepository.php`.
   - Servicio de Aplicación: `DocumentoElectronicoService.php` (métodos `registrarEmision`, `actualizarDesdeRespuesta`, `obtenerPorVenta`, `obtenerPorId`).
   - Inyección de dependencias registrada en `AppServiceProvider.php`.
   - Validación sintáctica PHP (`php -l`) verificada en todos los componentes con 0 errores.

4. **Incolumidad Comercial e Integridad**:
   - `VentaService`, flujo de ventas, caja, cartera, inventario y turnos se mantienen **100% INTACTOS** y sin conectar.

### OBJETIVO FUTURO (PENDIENTE)
- Integración `VentaService` -> `FacturacionService` (conversión Venta -> `SolicitudFacturaDTO`).
- Envío automático a MATIAS al confirmar/cerrar venta.
- Jobs, Queues, eventos y reintentos automáticos.
- Consulta de estado por `trackId`.
- Descarga y almacenamiento de archivos PDF y XML.
- Emisión de notas crédito/débito, Documento Soporte, RADIAN y nómina electrónica.

## 30. Fase 7A — Configuración de Catálogos Fiscales y Reglas de Facturación Electrónica (IMPLEMENTADO)

1. **Regla de Negocio Confirmada**:
   - Cuando `facturacion_electronica_activa = true`, toda venta que sea legalmente facturable debe generar documento electrónico.
   - Las ventas que no cuenten con los datos fiscales requeridos no impiden la creación de la venta comercial y quedan trazables como `pendientes` o con `error` de facturación.

2. **Migraciones y Estructuras Creadas (Ejecutadas Exitosamente con `php artisan migrate`)**:
   - `2026_09_16_000004_add_datos_fiscales_to_clientes_table.php`: Agrega columnas de datos fiscales a `clientes` (`tipo_persona`, `tipo_documento_id`, `tipo_organization_id`, `tax_regime_id`, `tax_level_id`, `codigo_postal`, `ciudad_id`, `pais_id`). Modelo `Cliente.php` actualizado.
   - `2026_09_16_000005_create_mapeos_catalogos_facturacion_table.php`: Crea la tabla `mapeos_catalogos_facturacion` para desacoplar equivalencias ERP -> Proveedor para medios de pago, unidades de medida, impuestos y documentos. Modelo `MapeoCatalogoFacturacion.php`.
   - `2026_09_16_000006_add_codigo_dian_to_unidades_medida_table.php`: Agrega `codigo_dian` y `simbolo_dian` a `unidades_medida`. Modelo `UnidadMedida.php` actualizado.
   - `2026_09_16_000007_add_reglas_to_configuracion_facturacion_table.php`: Agrega reglas de emisión a `configuracion_facturacion` (`facturar_ventas_pos`, `facturar_ventas_combustible`, `permitir_ventas_sin_datos_fiscales`). Modelo `ConfiguracionFacturacion.php` actualizado.

3. **Servicio de Aplicación (`CatalogosFacturacionService`)**:
   - `CatalogosFacturacionService.php`: Creado en `app/Modules/Facturacion/Application/Services/` para consultar dinámicamente las equivalencias entre los tipos del ERP y los catálogos de MATIAS/DIAN.
   - `MatiasInvoiceMapper.php`: Actualizado para utilizar las equivalencias dinámicas.

4. **Incolumidad Comercial e Integridad**:
   - `VentaService` **NO FUE MODIFICADO** ni conectado todavía.
   - Se validaron sintácticamente todos los archivos con `php -l` (0 errores).

### OBJETIVO FUTURO (PENDIENTE)
- Envío real de facturas a MATIAS Sandbox/Producción con token activo.
- Jobs, Queues, eventos y reintentos automáticos.
- Consulta de estado por `trackId`.
- Descarga y almacenamiento de archivos PDF y XML.
- Emisión de notas crédito/débito, Documento Soporte, RADIAN y nómina electrónica.

## 31. Fase 7B — Integración Venta → Facturación Electrónica (IMPLEMENTADO)

1. **Integración Comercial Desacoplada (`VentaService`)**:
   - `VentaService` inyecta opcionalmente `FacturacionService`, `DocumentoElectronicoService`, `ConfiguracionFacturacionRepositoryInterface` y `ConfiguracionEmpresaRepositoryInterface` con fallback de resolución mediante `app()`.
   - Se actualizaron los métodos de creación comercial `create()` (lubricantes/POS) y `createCombustible()` para ejecutar `$this->procesarFacturacionElectronica($ventaFinal)` únicamente tras el commit exitoso de la transacción principal en base de datos.

2. **Procesamiento Aislado y Trazabilidad (`procesarFacturacionElectronica`)**:
   - La llamada está 100% aislada mediante `try-catch` independiente. Si ocurriese cualquier excepción de red, timeout o mapeo, la venta comercial finaliza con éxito y no sufre rollback.
   - Si `facturacion_electronica_activa = false` en `ConfiguracionFacturacion`, la función retorna `null` de inmediato sin invocar al proveedor ni generar registros residuales.
   - Evalúa los interruptores de negocio `facturar_ventas_pos` y `facturar_ventas_combustible`.
   - Construye la `SolicitudFacturaDTO` mapeando cliente, emisor, ítems con impuestos/unidades y totales monetarios.
   - Registra el registro inicial en la tabla `documentos_electronicos` (`estado = 'pendiente'`).
   - Envía la solicitud a `FacturacionService::enviarFactura()`.
   - Actualiza el estado del documento a `'emitido'` o `'rechazado'` con CUFE, identificador externo, mensajes y detalles técnicos devueltos por el proveedor.

3. **Respeto Estricto de Reglas y Alcance**:
   - Se mantuvo 100% la incolumidad de los flujos de inventario, cartera, caja, turnos islero, precios y cancelaciones.
   - No se crearon Jobs, Queues, eventos, ni llamadas HTTP síncronas sin control de excepciones.

4. **Validación Sintáctica**:
   - Ejecutado `php -l` sobre todos los archivos del módulo de facturación y ventas con resultado exitoso (0 errores).

### OBJETIVO FUTURO (PENDIENTE)
- Habilitación de credenciales reales y envío HTTP activo a MATIAS API en Sandbox/Producción.
- Jobs, Queues y listeners de eventos asíncronos.
- Consulta de estado por `trackId`.
- Almacenamiento local o S3 de archivos PDF y XML de facturas.
- Emisión de notas crédito/débito, Documento Soporte, RADIAN y nómina electrónica.

## 32. Fase 8B-1 — Configuración de Empresa + Configuración de Facturación FRONT (IMPLEMENTADO)

1. **Configuración Fiscal de Empresa (`CompanySettingsPage.jsx`)**:
   - Actualizado [CompanySettingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/CompanySettingsPage.jsx) para cargar, editar y enviar a `PUT /api/configuracion-empresa` los campos fiscales agregados en Backend FASE 1: `tipo_persona`, `tipo_documento`, `tipo_regimen`, `responsabilidades_fiscales` (matriz de selección múltiple O-13, O-15, O-23, O-47, R-99-PN), `codigo_postal` y `matricula_mercantil`.
   - Se mantuvieron intactas las pestañas existentes (General, Logo, Ubicación, Impuestos y Facturación DIAN) y el soporte para subir imágenes de logo mediante `FormData`.

2. **Servicio API de Facturación (`invoicingSettingsService.js`)**:
   - Creado [invoicingSettingsService.js](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/services/invoicingSettingsService.js) bajo el patrón de servicios del proyecto (`fetch` con Bearer Token y JSON headers) para consumir `GET /api/configuracion-facturacion` y `PUT /api/configuracion-facturacion`.

3. **Pantalla de Configuración de Facturación (`InvoicingSettingsPage.jsx`)**:
   - Creado [InvoicingSettingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/InvoicingSettingsPage.jsx) para administrar:
     - `proveedor_activo`: `'matias'` (oficial).
     - `ambiente`: `'sandbox'` / `'produccion'`.
     - `facturacion_electronica_activa`: Interruptor general booleano.
     - `facturar_ventas_pos`: Interruptor booleano para ventas de lubricantes / POS.
     - `facturar_ventas_combustible`: Interruptor booleano para despachos en isla.
     - `permitir_ventas_sin_datos_fiscales`: Interruptor booleano para tolerar consumidor final.
     - `reintentos_automaticos` y `max_reintentos`: Configuración de resiliencia con control numérico validado (1 a 10).

4. **Navegación y Rutas (`App.jsx` y `Sidebar.jsx`)**:
   - Registrada la ruta `/facturacion` apuntando a `<InvoicingSettingsPage />` dentro de `<MainLayout>` en [App.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/App.jsx).
   - Agregada la entrada "Facturación Electrónica" en el menú de navegación [Sidebar.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/components/Sidebar.jsx) bajo el grupo "Configuración".

5. **Incolumidad y Validaciones**:
   - El Backend PHP y la Base de Datos se mantuvieron **100% INTACTOS** (0 modificaciones).
   - Verificación sintáctica estática de React/JS mediante ESLint finalizada exitosamente con 0 errores.

### OBJETIVO FUTURO (PENDIENTE)
- Fase 8B-2: Campos fiscales en formulario de Cliente (`ClientModal.jsx`).
- Fase 8B-3: Código DIAN en Unidades de Medida (`UnitModal.jsx`).
- Fase 8B-4: Interfaz de Mapeos de Catálogos (`mapeos_catalogos_facturacion`).
- Vista de trazabilidad de Documentos Electrónicos (`documentos_electronicos`).

## 33. Fase 8B-2A — Corrección de contratos API Backend para Clientes + Unidades de Medida (IMPLEMENTADO)

1. **Módulo Clientes (`app/Modules/Clientes`)**:
   - `StoreClienteRequest.php` & `UpdateClienteRequest.php`: Agregadas reglas de validación nullable para los 8 campos fiscales: `tipo_persona`, `tipo_documento_id`, `tipo_organization_id`, `tax_regime_id`, `tax_level_id`, `codigo_postal`, `ciudad_id`, `pais_id`.
   - `CreateClienteDTO.php` & `UpdateClienteDTO.php`: Agregadas propiedades públicas nullable para los 8 campos fiscales.
   - `ClienteMapper.php`: Mapeo de los 8 campos fiscales en `fromArrayToCreateDTO` y `fromArrayToUpdateDTO`.
   - `ClienteService.php`: Inclusión de los 8 campos fiscales en las cargas útiles enviadas al repositorio en `create()` y `update()`.
   - `ClienteResource.php`: Exposición de los 8 campos fiscales en el arreglo devuelto por `toArray()`.

2. **Módulo Unidades de Medida (`app/Modules/UnidadesMedida`)**:
   - `StoreUnidadMedidaRequest.php` & `UpdateUnidadMedidaRequest.php`: Agregadas reglas de validación nullable string (`max:50`) para `codigo_dian` y `simbolo_dian`.
   - `CreateUnidadMedidaDTO.php` & `UpdateUnidadMedidaDTO.php`: Agregadas propiedades públicas nullable para `codigo_dian` y `simbolo_dian`.
   - `UnidadMedidaMapper.php`: Mapeo de `codigo_dian` y `simbolo_dian` en `fromArrayToCreateDTO` y `fromArrayToUpdateDTO`.
   - `UnidadMedidaService.php`: Inclusión de `codigo_dian` y `simbolo_dian` en las cargas útiles enviadas al repositorio en `create()` y `update()`.
   - `UnidadMedidaResource.php`: Exposición de `codigo_dian` y `simbolo_dian` en el arreglo devuelto por `toArray()`.

3. **Incolumidad y Validaciones**:
   - Se mantuvo la compatibilidad total con los campos comerciales existentes.
   - NO se modificó la Base de Datos, migraciones ni Frontend.
   - Verificación sintáctica PHP (`php -l`) exitosa en todos los archivos de los módulos Clientes y UnidadesMedida con 0 errores.

### OBJETIVO FUTURO (PENDIENTE)
- Fase 8B-4: Interfaz de Mapeos de Catálogos (`mapeos_catalogos_facturacion`).
- Vista de trazabilidad de Documentos Electrónicos (`documentos_electronicos`).

## 34. Fase 8B-2 — Clientes + Unidades de Medida FRONT (IMPLEMENTADO)

### IMPLEMENTADO

1. **Formulario de Clientes (`ClientModal.jsx`)**:
   - Integrados los 8 campos fiscales en la interfaz React manteniendo todos los campos comerciales existentes: `tipo_persona`, `tipo_documento_id`, `tipo_organization_id`, `tax_regime_id`, `tax_level_id`, `codigo_postal`, `ciudad_id`, `pais_id`.
   - Organizado en bloques visuales claros: "Datos Generales", "Datos Fiscales (DIAN)" y "Ubicación y Dirección".
   - Soporte dinámico para selección en cascada de País -> Departamento -> Ciudad reutilizando las rutas `/api/ubicaciones/...` vía `companyService.js`.
   - Soporte completo para modo creación y modo edición (precarga de los 8 campos fiscales y resolución dinámica de ubicación).
   - Sanitización del payload en el submit: conversión de campos opcionales vacíos a `null` y eliminación de campos auxiliares transitorios de UI.

2. **Formulario de Unidades de Medida (`UnitModal.jsx`)**:
   - Integrados los campos DIAN de facturación electrónica: `codigo_dian` y `simbolo_dian`.
   - Soporte completo para creación y edición, precargando los valores devueltos por la API y formateando los campos opcionales en el payload.

3. **Integración con Servicios Frontend y APIs**:
   - Reutilización directa de `clientService.js` y `unitService.js` utilizando los contratos REST existentes (`POST/PUT /api/clientes` y `POST/PUT /api/unidades-medida`).
   - Sin llamadas a endpoints inventados ni servicios externos.

4. **Incolumidad y Verificaciones**:
   - Backend PHP, modelos, controladores, migraciones y base de datos **100% INTACTOS** (0 modificaciones).
   - Compilación exitosa de frontend mediante `npm run build` (Vite) con 0 errores.

### PENDIENTE / FUTURO

- Fase 8B-3: Interfaz Frontend para Mapeos de Catálogos (`CatalogMappingsPage.jsx`, `catalogMappingsService.js`, navegación y permisos).
- Vista de trazabilidad de Documentos Electrónicos (`documentos_electronicos`).
- Emisión de facturas electrónicas desde FRONT.

## 35. Fase 8B-3A — API REST + Permisos para Mapeos de Catálogos de Facturación (IMPLEMENTADO)

### IMPLEMENTADO

1. **Capa REST Mapeos de Catálogos (`app/Modules/Facturacion`)**:
   - DTOs: [CreateMapeoCatalogoFacturacionDTO.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Application/DTOs/CreateMapeoCatalogoFacturacionDTO.php) y [UpdateMapeoCatalogoFacturacionDTO.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Application/DTOs/UpdateMapeoCatalogoFacturacionDTO.php).
   - Interfaz e Infraestructura de Repositorio: [MapeoCatalogoFacturacionRepositoryInterface.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Application/Interfaces/MapeoCatalogoFacturacionRepositoryInterface.php) y [MapeoCatalogoFacturacionRepository.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Infrastructure/Repositories/MapeoCatalogoFacturacionRepository.php) con soporte de paginación, filtros dinámicos (`search`, `proveedor`, `categoria`, `codigo_interno`, `is_active`) y validación de duplicados a nivel de aplicación.
   - Servicio de Aplicación: [MapeoCatalogoFacturacionService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Application/Services/MapeoCatalogoFacturacionService.php) que valida duplicados (422) y orquesta operaciones CRUD.
   - Mapper: [MapeoCatalogoFacturacionMapper.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Infrastructure/Mappers/MapeoCatalogoFacturacionMapper.php).
   - Form Requests: [StoreMapeoCatalogoFacturacionRequest.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Presentation/Requests/StoreMapeoCatalogoFacturacionRequest.php) y [UpdateMapeoCatalogoFacturacionRequest.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Presentation/Requests/UpdateMapeoCatalogoFacturacionRequest.php).
   - Resource JSON: [MapeoCatalogoFacturacionResource.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Presentation/Resources/MapeoCatalogoFacturacionResource.php).
   - Controlador: [MapeoCatalogoFacturacionController.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Presentation/Controllers/MapeoCatalogoFacturacionController.php) resguardado con permisos Spatie y utilizando `ApiResponse`.
   - Inyección de dependencias: Registrada la interfaz en [AppServiceProvider.php](file:///c:/xampp82/htdocs/gasstation_back/app/Providers/AppServiceProvider.php).

2. **Rutas API REST (`routes/api.php`)**:
   - Registrado el grupo `/api/mapeos-catalogos` bajo el middleware `auth:sanctum`:
     - `GET /api/mapeos-catalogos` (Listar / Filtrar)
     - `POST /api/mapeos-catalogos` (Crear)
     - `GET /api/mapeos-catalogos/{id}` (Ver detalle)
     - `PUT /api/mapeos-catalogos/{id}` (Actualizar)
     - `DELETE /api/mapeos-catalogos/{id}` (Eliminar)

3. **Permisos y Roles Spatie (`RolesAndPermissionsSeeder.php`)**:
   - Registrados 4 permisos específicos bajo la convención del proyecto:
     - `ver_mapeos_catalogos`
     - `crear_mapeos_catalogos`
     - `editar_mapeos_catalogos`
     - `eliminar_mapeos_catalogos`
   - Sincronizados exitosamente con el rol `admin` y el usuario administrador mediante `php artisan db:seed --class=RolesAndPermissionsSeeder`.

4. **Incolumidad y Validaciones**:
   - Frontend **100% INTACTO** (0 archivos React/JS modificados).
   - Base de datos y migraciones **100% INTACTAS** (0 cambios de tabla/esquema).
   - `VentaService`, `FacturacionService`, `MatiasProvider`, `MatiasClient`, POS y flujo comercial **100% INTACTOS**.
   - Verificación sintáctica PHP (`php -l`) completada con 0 errores en todos los módulos.
   - Verificación de rutas de Laravel (`php artisan route:list`) confirmada con los 5 endpoints activos.

### PENDIENTE / FUTURO

- Vista de trazabilidad de Documentos Electrónicos (`documentos_electronicos`).
- Emisión de facturas electrónicas desde FRONT.

---

## 36. Fase 8B-3 — Mapeos de Catálogos de Facturación FRONT (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se implementó exclusivamente en **FRONTEND** la interfaz administrativa completa para la gestión de mapeos de catálogos de facturación electrónica (`mapeos_catalogos_facturacion`), integrada con la API REST y permisos Spatie existentes del Backend.

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Archivos Creados (Nuevos)**:
   - [catalogMappingsService.js](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/services/catalogMappingsService.js): Servicio Frontend para consumo de la API REST `/api/mapeos-catalogos`.
   - [CatalogMappingModal.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/components/CatalogMappingModal.jsx): Componente Modal para la creación y edición de mapeos de catálogo.
   - [CatalogMappingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/CatalogMappingsPage.jsx): Pantalla administrativa principal con tabla, filtros dinámicos, exportación a Excel, cambio de estado y eliminación.

2. **Archivos Modificados**:
   - [App.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/App.jsx): Registrada la ruta `/facturacion/mapeos` protegida en `MainLayout`.
   - [Sidebar.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/components/Sidebar.jsx): Agregado el acceso "Mapeos de Catálogos" en el grupo de navegación "Configuración" protegido con el permiso `ver_mapeos_catalogos`.
   - [InvoicingSettingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/InvoicingSettingsPage.jsx): Agregado botón de acceso rápido "Mapeos Catálogos" condicionado al permiso `ver_mapeos_catalogos`.

### CONTRATO REST Y PERMISOS UTILIZADOS

- **Endpoints Backend Consumidos**:
  - `GET /api/mapeos-catalogos`: Consulta de listado con filtros (`search`, `categoria`, `is_active`).
  - `POST /api/mapeos-catalogos`: Creación de mapeos.
  - `GET /api/mapeos-catalogos/{id}`: Detalle de mapeo.
  - `PUT /api/mapeos-catalogos/{id}`: Edición de campos y actualización de estado `is_active`.
  - `DELETE /api/mapeos-catalogos/{id}`: Eliminación física de mapeos.
- **Categorías soportadas**: `tipo_documento`, `medio_pago`, `unidad_medida`, `impuesto`.
- **Integración de Permisos Spatie (`usePermissions`)**:
  - `ver_mapeos_catalogos`: Control de acceso a la pantalla, menú lateral y botones de navegación.
  - `crear_mapeos_catalogos`: Control visual del botón "Nuevo Mapeo".
  - `editar_mapeos_catalogos`: Control visual de las acciones de edición y toggle de estado (`Power`).
  - `eliminar_mapeos_catalogos`: Control visual de las acciones de eliminación con modal de confirmación (`ConfirmModal`).

### VALIDACIONES REALIZADAS

- **Build de Producción**: `npm run build` ejecutado en `front/` con resultado **exitoso (exit code 0)** (`built in 29.07s`).
- **ESLint**: `npx eslint` ejecutado en todos los archivos modificados/creados con resultado **exitoso (0 errores, exit code 0)**.
- **Incolumidad Backend**: Cero archivos PHP, migraciones, modelos, controladores o configuraciones backend modificadas.
- **Incolumidad MATIAS**: Cero llamadas externas HTTP o pruebas de facturación realizadas.

---

## 37. Corrección de método en VentaService y FacturacionService (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se corrigió la llamada al método inanimado/inexistente `get()` sobre la interfaz `ConfiguracionFacturacionRepositoryInterface` y `ConfiguracionEmpresaRepositoryInterface`, sustituyéndola por la llamada a `first()`, método oficialmente contratado en las interfaces e implementado en los repositorios.

### ARCHIVOS MODIFICADOS
- [VentaService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Application/Services/VentaService.php): Reemplazadas llamadas `$this->configuracionFacturacionRepository->get()` y `$this->configuracionEmpresaRepository?->get()` por `->first()`.
- [FacturacionService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Facturacion/Application/Services/FacturacionService.php): Reemplazadas llamadas `$this->configuracionRepository->get()` por `->first()`.

### VALIDACIONES
- Sintaxis PHP (`php -l`): **0 errores**.
- Pruebas automatizadas (`php artisan test`): **2/2 pasadas (Exit status 0)**.

---

## 38. Fase Correctiva — Configuración de Resoluciones y Prefijos por Tipo de Documento (IMPLEMENTADO)

### PROBLEMA DETECTADO Y SOLUCIÓN ARQUITECTÓNICA
Anteriormente, los campos `prefijo_factura` y `numero_resolucion` estaban acoplados de forma global en `configuracion_empresa`, lo que impedía gestionar resoluciones o prefijos independientes para diferentes tipos de documentos electrónicos (`factura`, `nota_credito`, `nota_debito`, `documento_soporte`, `pos_electronico`, `eventos`).

Se creó una arquitectura modular desacoplada mediante la entidad `ResolucionFacturacion` y el módulo `app/Modules/ResolucionesFacturacion`, permitiendo la asignación y consulta de resoluciones por tipo de documento sin alterar el esquema de `configuracion_empresa` ni acoplarse al proveedor técnico MATIAS.

### TABLA Y MIGRACIÓN CREADA
- **Migración:** `2026_09_27_000001_create_resoluciones_facturacion_table.php`
- **Tabla DB:** `resoluciones_facturacion`
- **Campos principales:** `id`, `configuracion_empresa_id`, `tipo_documento`, `prefijo`, `numero_resolucion`, `fecha_resolucion`, `rango_desde`, `rango_hasta`, `consecutivo_actual`, `fecha_vencimiento`, `clave_tecnica`, `proveedor`, `ambiente`, `is_active`, `timestamps()`.
- **Población Inicial / Migración:** Se migró automáticamente la resolución previa hacia `tipo_documento = 'factura'`.

### COMPATIBILIDAD CON CONFIGURACIÓN ACTUAL
Se preservaron intactas las columnas `prefijo_factura` y `numero_resolucion` en `configuracion_empresa` para mantener retrocompatibilidad.

### EVOLUCIÓN DEL FLUJO EN VENTASERVICE
El flujo comercial evolucionó hacia:
`Venta` ➔ `ResolucionFacturacionRepository::findByTipoDocumento('factura')` ➔ `Obtener prefijo + resolución` ➔ `Validar estado activo y presencia de datos` ➔ `SolicitudFacturaDTO` ➔ `FacturacionService` ➔ `MatiasProvider` ➔ `MATIAS`.

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Archivos Creados**:
   - `database/migrations/2026_09_27_000001_create_resoluciones_facturacion_table.php`
   - [ResolucionFacturacion.php](file:///c:/xampp82/htdocs/gasstation_back/app/Models/ResolucionFacturacion.php)
   - [CreateResolucionFacturacionDTO.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/ResolucionesFacturacion/Application/DTOs/CreateResolucionFacturacionDTO.php)
   - [ResolucionFacturacionRepositoryInterface.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/ResolucionesFacturacion/Application/Interfaces/ResolucionFacturacionRepositoryInterface.php)
   - [ResolucionFacturacionRepository.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/ResolucionesFacturacion/Infrastructure/Repositories/ResolucionFacturacionRepository.php)
   - [ResolucionFacturacionService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/ResolucionesFacturacion/Application/Services/ResolucionFacturacionService.php)

2. **Archivos Modificados**:
   - [AppServiceProvider.php](file:///c:/xampp82/htdocs/gasstation_back/app/Providers/AppServiceProvider.php) *(Registrado binding de ResolucionFacturacionRepositoryInterface)*
   - [VentaService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Application/Services/VentaService.php) *(Inyectada la interfaz y actualizado el flujo de resolución por tipo de documento)*

### VALIDACIONES EJECUTADAS
- **Sintaxis PHP (`php -l`):** 0 errores en todos los módulos creados y modificados.
- **Pruebas Automatizadas (`php artisan test`):** 2/2 pasadas (Exit code 0).
- **Simulación Dry-Run de Mapeo (sin HTTP):** Confirmado que la emisión de factura toma `resolution_number` y `prefix` de `resoluciones_facturacion` y genera `document_number` como entero.
- **Seguridad e Incolumidad:** 0 llamadas reales a MATIAS API ejecutadas; 0 modificaciones realizadas al Frontend.

---

## 39. FASE 8B-4A — API REST + PERMISOS PARA RESOLUCIONES DE FACTURACIÓN (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se implementó exclusivamente en el BACKEND la capa de servicios REST y los permisos Spatie necesarios para administrar la entidad `resoluciones_facturacion`, preparando el sistema para el posterior consumo desde el Frontend (FASE 8B-4B).

### RUTAS REST DISPONIBLES
- `GET    /api/resoluciones-facturacion` (Permiso: `ver_resoluciones_facturacion`)
- `GET    /api/resoluciones-facturacion/{id}` (Permiso: `ver_resoluciones_facturacion`)
- `POST   /api/resoluciones-facturacion` (Permiso: `crear_resoluciones_facturacion`)
- `PUT    /api/resoluciones-facturacion/{id}` (Permiso: `editar_resoluciones_facturacion`)
- `DELETE /api/resoluciones-facturacion/{id}` (Permiso: `eliminar_resoluciones_facturacion`)

### PERMISOS SPATIE REGISTRADOS (EN ROLESANDPERMISSIONSSEEDER)
- `ver_resoluciones_facturacion`
- `crear_resoluciones_facturacion`
- `editar_resoluciones_facturacion`
- `eliminar_resoluciones_facturacion`

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Archivos Creados**:
   - `app/Modules/ResolucionesFacturacion/Application/DTOs/UpdateResolucionFacturacionDTO.php`
   - `app/Modules/ResolucionesFacturacion/Infrastructure/Mappers/ResolucionFacturacionMapper.php`
   - `app/Modules/ResolucionesFacturacion/Presentation/Requests/StoreResolucionFacturacionRequest.php`
   - `app/Modules/ResolucionesFacturacion/Presentation/Requests/UpdateResolucionFacturacionRequest.php`
   - `app/Modules/ResolucionesFacturacion/Presentation/Resources/ResolucionFacturacionResource.php`
   - `app/Modules/ResolucionesFacturacion/Presentation/Controllers/ResolucionFacturacionController.php`

2. **Archivos Modificados**:
   - `app/Modules/ResolucionesFacturacion/Application/Interfaces/ResolucionFacturacionRepositoryInterface.php`
   - `app/Modules/ResolucionesFacturacion/Infrastructure/Repositories/ResolucionFacturacionRepository.php`
   - `app/Modules/ResolucionesFacturacion/Application/Services/ResolucionFacturacionService.php`
   - `routes/api.php`
   - `database/seeders/RolesAndPermissionsSeeder.php`

### VALIDACIONES EJECUTADAS Y RESULTADOS
- **Sintaxis PHP (`php -l`):** 11/11 archivos PHP verificados sin ningún error sintáctico (Exit code 0).
- **Seeder de Permisos (`php artisan db:seed --class=RolesAndPermissionsSeeder`):** Ejecutado exitosamente (Exit code 0).
- **Listado de Rutas (`php artisan route:list --path=api/resoluciones-facturacion`):** Confirmadas las 5 rutas registradas bajo `auth:sanctum` (Exit code 0).
- **Pruebas Automatizadas (`php artisan test`):** 2/2 pasadas exitosamente (Exit code 0).
- **Incolumidad:** 0 llamadas HTTP realizadas a MATIAS API, 0 modificaciones realizadas al Frontend y 0 modificaciones realizadas a `VentaService`.

---

## 40. FASE 8B-4B — FRONTEND: ADMINISTRACIÓN DE RESOLUCIONES DE FACTURACIÓN (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se implementó exclusivamente en el FRONTEND la pantalla de administración de resoluciones de facturación electrónica (`/facturacion/resoluciones`), permitiendo la administración completa CRUD de resoluciones DIAN por tipo de documento consumiendo el API REST existente en Laravel.

### RUTA FRONTEND NAVEGABLE
- `/facturacion/resoluciones`

### PERMISOS APLICADOS (VÍA USEPERMISSIONS)
- `ver_resoluciones_facturacion`: Controla la visualización de la pantalla y el acceso desde la navegación (Sidebar y botón en InvoicingSettingsPage).
- `crear_resoluciones_facturacion`: Muestra el botón "Nueva Resolución" y permite abrir el modal de creación.
- `editar_resoluciones_facturacion`: Permite la edición de campos y cambio de estado activo/inactivo (Power toggle).
- `eliminar_resoluciones_facturacion`: Permite la eliminación de registros previa confirmación con `ConfirmModal`.

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Archivos Creados**:
   - `front/src/features/settings/services/resolucionesFacturacionService.js`
   - `front/src/features/settings/components/ResolucionFacturacionModal.jsx`
   - `front/src/features/settings/pages/ResolucionesFacturacionPage.jsx`

2. **Archivos Modificados**:
   - `front/src/features/settings/pages/InvoicingSettingsPage.jsx` *(Agregado botón "Resoluciones" en el encabezado protegido por `ver_resoluciones_facturacion`)*
   - `front/src/components/Sidebar.jsx` *(Agregada opción "Resoluciones Facturación" en la sección de Configuración)*
   - `front/src/App.jsx` *(Registrada la ruta `/facturacion/resoluciones` dentro de `MainLayout`)*

### VALIDACIONES Y RESULTADOS
- **Build de Producción (`npm run build`):** Ejecutado en `front/` con resultado **exitoso (Exit status 0)** (`built in 34.13s`).
- **ESLint (`npx eslint`):** Ejecutado en los archivos frontend creados y modificados con resultado **exitoso (0 errores, Exit status 0)**.
- **Incolumidad Backend:** Cero archivos PHP, modelos, controladores o migraciones backend modificadas.
- **Incolumidad MATIAS:** Cero llamadas reales ejecutadas a la API de MATIAS.

---

## 41. FASE 8B-4C — REFACTORIZACIÓN DE LA CONFIGURACIÓN DE FACTURACIÓN ELECTRÓNICA (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se refactorizó exclusivamente la estructura y navegación del FRONTEND para consolidar la administración de facturación electrónica en un único módulo unificado (`/facturacion`), organizando sus áreas mediante pestañas internas y eliminando elementos duplicados de la navegación global.

### PESTAÑAS INTERNAS CONSOLIDADAS
- `[ General ]`: Parámetros generales y habilitación de facturación.
- `[ Proveedor ]`: Configuración del proveedor tecnológico MATIAS (token, ambiente, URLs).
- `[ Resoluciones ]`: Integración embedded de la administración CRUD de resoluciones DIAN por tipo de documento.
- `[ Catálogos ]`: Integración embedded del mapeo de catálogos DIAN (medios de pago, unidades de medida, tributos).

### REORGANIZACIÓN Y REFACTORIZACIÓN APLICADA
1. **Consolidación en `InvoicingSettingsPage.jsx`**: Reconstruida con navegación por pestañas internas sincronizadas con la query string `?tab=` y control de permisos Spatie (`ver_resoluciones_facturacion`, `ver_mapeos_catalogos`).
2. **Reubicación de Matrícula Mercantil en `CompanySettingsPage.jsx`**: Se eliminó la pestaña duplicada "Facturación DIAN" y se movió el campo `matricula_mercantil` dentro de la pestaña "Impuestos y Fiscal", preservando intactos los endpoints y DTOs de empresa.
3. **Modo Embedded (`embedded={true}`)**: Agregada la prop `embedded` a `ResolucionesFacturacionPage.jsx` y `CatalogMappingsPage.jsx` para ocultar títulos/subtítulos duplicados cuando se renderizan dentro de las pestañas de `InvoicingSettingsPage`.
4. **Navegación Simplificada (`Sidebar.jsx`)**: Eliminados los ítems independientes "Mapeos de Catálogos" y "Resoluciones Facturación" del grupo "Configuración", unificando el acceso en "Facturación Electrónica".
5. **Retrocompatibilidad de Rutas (`App.jsx`)**: Mapeadas las rutas directas `/facturacion/mapeos` y `/facturacion/resoluciones` hacia `<InvoicingSettingsPage initialTab="catalogos" />` y `<InvoicingSettingsPage initialTab="resoluciones" />`.

### ARCHIVOS MODIFICADOS
- [CompanySettingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/CompanySettingsPage.jsx)
- [CatalogMappingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/CatalogMappingsPage.jsx)
- [ResolucionesFacturacionPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/ResolucionesFacturacionPage.jsx)
- [InvoicingSettingsPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/settings/pages/InvoicingSettingsPage.jsx)
- [Sidebar.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/components/Sidebar.jsx)
- [App.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/App.jsx)

### VALIDACIONES Y RESULTADOS
- **Build de Producción (`npm run build`):** Ejecutado en `front/` con resultado **exitoso (Exit status 0)** (`built in 16.45s`).
- **ESLint (`npx eslint`):** Ejecutado en los 6 archivos frontend modificados con resultado **exitoso (0 errores, Exit status 0)**.
- **Incolumidad Backend:** Cero archivos PHP modificados, 0 migraciones, 0 controladores, 0 servicios backend, 0 llamadas a MATIAS API.

---

## 42. INTEGRACIÓN DE FACTURACIÓN ELECTRÓNICA EN VENTAS (COMBUSTIBLE Y LUBRICANTES) (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se implementó la facturación electrónica como una decisión explícita del usuario (`factura_electronica = true/false`) en las ventas de combustible (`FuelSalesPage.jsx`) y lubricantes/POS (`LubricantSalesPage.jsx`).

### REGLAS DE NEGOCIO APLICADAS
1. **Factura Electrónica Identificada (`factura_electronica = true`)**:
   - Requiere obligatoriamente un cliente identificado en el backend y frontend.
   - Utiliza exclusivamente los datos reales del cliente (`documento`, `nombre_razon_social`, `email`, `telefono`, `direccion`, datos fiscales DIAN).
   - Prohíbe terminantemente la sustitución de datos por valores ficticios (`CONSUMIDOR FINAL`, `222222222222`, `factura@cliente.com`, `3000000000`, `Dirección Conocida`). Si el cliente no tiene datos válidos, se rechaza la venta con HTTP 422.
   - Si el cliente no existe en la base de datos, permite abrir `ClientModal.jsx`, registrar el cliente con el flujo existente y seleccionarlo automáticamente.
2. **Venta Normal (`factura_electronica = false`)**:
   - Si no se solicita factura electrónica identificada, la venta procesa el flujo normal de consumidor final únicamente cuando la configuración global (`facturar_ventas_pos` o `facturar_ventas_combustible`) lo exige, preservando intacta la compatibilidad global del sistema.

### TABLA Y MIGRACIÓN BACKEND
- **Migración:** `2026_09_27_000002_add_factura_electronica_to_ventas_table.php`
- **Columna agregada:** `ventas.factura_electronica` (`boolean`, `default(false)`).

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Backend**:
   - `database/migrations/2026_09_27_000002_add_factura_electronica_to_ventas_table.php`
   - [Venta.php](file:///c:/xampp82/htdocs/gasstation_back/app/Models/Venta.php)
   - [CreateVentaDTO.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Application/DTOs/CreateVentaDTO.php)
   - [CreateVentaCombustibleDTO.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Application/DTOs/CreateVentaCombustibleDTO.php)
   - [StoreVentaRequest.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Presentation/Requests/StoreVentaRequest.php)
   - [StoreVentaCombustibleRequest.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Presentation/Requests/StoreVentaCombustibleRequest.php)
   - [VentaMapper.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Infrastructure/Mappers/VentaMapper.php)
   - [VentaResource.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Presentation/Resources/VentaResource.php)
   - [VentaService.php](file:///c:/xampp82/htdocs/gasstation_back/app/Modules/Ventas/Application/Services/VentaService.php)
   - `tests/Feature/FacturacionElectronicaVentasDryRunTest.php`

2. **Frontend**:
   - [FuelSalesPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/sales/pages/FuelSalesPage.jsx)
   - [LubricantSalesPage.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/sales/pages/LubricantSalesPage.jsx)
   - [ClientModal.jsx](file:///c:/xampp82/htdocs/gasstation_back/front/src/features/clients/components/ClientModal.jsx)

### VALIDACIONES Y RESULTADOS DE PRUEBAS
- **Sintaxis PHP (`php -l`):** 9/9 archivos PHP verificados sin ningún error sintáctico (Exit code 0).
- **Migraciones (`php artisan migrate`):** Migración ejecutada exitosamente (**Exit status 0**).
- **Pruebas Automatizadas (`php artisan test`):** Pasadas (**27/27 assertions**).
- **Suite Dry-Run (`FacturacionElectronicaVentasDryRunTest`):** **4/4 test cases pasados (23 assertions)**:
  - **Caso A**: `factura_electronica = true` con cliente real $\rightarrow$ Genera DTO y payload MATIAS con datos reales.
  - **Caso B**: `factura_electronica = true` sin cliente $\rightarrow$ Rechazado con excepción 422.
  - **Caso C**: `factura_electronica = false` sin facturación global $\rightarrow$ Omite emisión de factura.
  - **Caso D**: `factura_electronica = false` con facturación global $\rightarrow$ Mantiene consumidor final.
- **Build de Producción (`npm run build`):** Ejecutado en `front/` con resultado **exitoso (Exit status 0)** (`built in 28.22s`).
- **ESLint (`npx eslint`):** Ejecutado en archivos frontend con resultado **exitoso (0 errores, Exit status 0)**.
- **Incolumidad de Seguridad:** 0 llamadas reales a la API de MATIAS ejecutadas; 0 modificaciones en resoluciones o consecutivos; 0 modificaciones en descarga de PDF/XML/QR.

---

## 43. FASE 8B-5 — CONSULTA DE DOCUMENTOS ELECTRÓNICOS (IMPLEMENTADO)

### OBJETIVO ALCANZADO
Se implementó la nueva pestaña de consulta **"Documentos Electrónicos"** dentro del módulo consolidado de **Facturación Electrónica** (`InvoicingSettingsPage.jsx`), permitiendo la inspección de la trazabilidad técnica de emisiones registradas en la tabla `documentos_electronicos`.

### COMPONENTES Y ARQUITECTURA
1. **Seguridad y Permisos (Spatie)**:
   - Creado el permiso `ver_documentos_electronicos` y asignado al rol `admin` en `RolesAndPermissionsSeeder.php`.
2. **Backend**:
   - **Contrato & Repositorio:** `DocumentoElectronicoRepositoryInterface` y `DocumentoElectronicoRepository` implementan `paginate(array $filters, int $perPage = 15)`.
   - **Filtro de Fechas Obligatorio:** Filtrado SQL en `created_at` usando `whereDate('created_at', '>=', $fechaInicial)` y `whereDate('created_at', '<=', $fechaFinal)`.
   - **Eager Loading:** Carga eficiente de `['venta.cliente', 'configuracionFacturacion']`.
   - **Servicio:** `DocumentoElectronicoService` expone la paginación de trazabilidad.
   - **Request:** `GetDocumentosElectronicosRequest` valida formato YYYY-MM-DD y exigencia de rango.
   - **Resource:** `DocumentoElectronicoResource` formatea atributos directos e información derivada (`venta_total`, `cliente_nombre`, `cliente_documento`).
   - **Controller & Ruta:** `DocumentoElectronicoController@index` expuesto en `GET /api/documentos-electronicos` bajo middleware `auth:sanctum`.
3. **Frontend**:
   - **Servicio:** `documentosElectronicosService.js` en `front/src/features/settings/services/`.
   - **Componente Embebido:** `ElectronicInvoicesTab.jsx` con rango de fechas por defecto (`HOY -> HOY`), campo de búsqueda, selector de estado, Badges de color (`emitido`, `pendiente`, `rechazado`/`error`), paginación y modal de inspección técnica en solo lectura (mostrando JSON `datos_tecnicos`, CUFE, UUID MATIAS, errores y mensajes).
   - **Integración:** Pestaña `"Documentos Electrónicos"` registrada en `InvoicingSettingsPage.jsx` protegida por `ver_documentos_electronicos`.

### ARCHIVOS CREADOS Y MODIFICADOS

1. **Backend**:
   - `database/seeders/RolesAndPermissionsSeeder.php` [MODIFICADO]
   - `app/Modules/Facturacion/Application/Interfaces/DocumentoElectronicoRepositoryInterface.php` [MODIFICADO]
   - `app/Modules/Facturacion/Infrastructure/Repositories/DocumentoElectronicoRepository.php` [MODIFICADO]
   - `app/Modules/Facturacion/Application/Services/DocumentoElectronicoService.php` [MODIFICADO]
   - `app/Modules/Facturacion/Presentation/Requests/GetDocumentosElectronicosRequest.php` [NUEVO]
   - `app/Modules/Facturacion/Presentation/Resources/DocumentoElectronicoResource.php` [NUEVO]
   - `app/Modules/Facturacion/Presentation/Controllers/DocumentoElectronicoController.php` [NUEVO]
   - `routes/api.php` [MODIFICADO]
   - `tests/Feature/ConsultaDocumentosElectronicosTest.php` [NUEVO]

2. **Frontend**:
   - `front/src/features/settings/services/documentosElectronicosService.js` [NUEVO]
   - `front/src/features/settings/components/ElectronicInvoicesTab.jsx` [NUEVO]
   - `front/src/features/settings/pages/InvoicingSettingsPage.jsx` [MODIFICADO]

### RESULTADOS DE VALIDACIÓN Y PRUEBAS
- **Sintaxis PHP (`php -l`):** 9/9 archivos PHP verificados sin errores (Exit status 0).
- **Seeding de Permisos (`php artisan db:seed`):** `RolesAndPermissionsSeeder` ejecutado limpiamente (Exit status 0).
- **Verificación de Ruta (`php artisan route:list`):** `GET|HEAD api/documentos-electronicos` confirmado.
- **Pruebas Automatizadas Backend (`php artisan test`):**
  - Suite de características `ConsultaDocumentosElectronicosTest`: **5/5 test cases pasados (15 assertions)**.
    - Caso A: `fecha_inicial = hoy, fecha_final = hoy` $\rightarrow$ Solo retorna documentos de hoy.
    - Caso B: Rango de varios días $\rightarrow$ Solo retorna documentos en el rango.
    - Caso C: `fecha_inicial > fecha_final` $\rightarrow$ Retorna 422 de validación.
    - Caso D: Usuario sin `ver_documentos_electronicos` $\rightarrow$ Retorna 403 Forbidden.
    - Caso E: Rango sin documentos $\rightarrow$ Retorna 200 vacio paginado.
  - Suite Completa del Sistema: **11/11 tests pasados (40 assertions, Exit status 0)**.
- **ESLint Frontend (`npx eslint`):** 0 errores (Exit status 0).
- **Build de Producción Frontend (`npm run build`):** Exitoso (`built in 35.84s`, Exit status 0).
- **Garantías de Seguridad:** 0 llamadas reales a MATIAS API; 0 modificaciones a `VentaService::procesarFacturacionElectronica()`; 0 modificaciones a consecutivo o resoluciones; 0 descargas creadas/modificadas.


