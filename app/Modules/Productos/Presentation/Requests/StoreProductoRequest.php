<?php

namespace App\Modules\Productos\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Producto;

class StoreProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tipo = $this->input('tipo_impuesto');
        if (is_string($tipo)) {
            $tipoNormalized = strtolower(trim($tipo));
            $merge = ['tipo_impuesto' => $tipoNormalized];

            if (in_array($tipoNormalized, [Producto::TIPO_IMPUESTO_EXENTO, Producto::TIPO_IMPUESTO_EXCLUIDO], true) && !$this->has('porcentaje_iva')) {
                $merge['porcentaje_iva'] = 0;
            }

            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:100', 'unique:productos,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'marca_id' => ['nullable', 'integer', 'exists:marcas,id'],
            'categoria_producto_id' => ['required', 'integer', 'exists:categorias_producto,id'],
            'unidad_medida_id' => ['required', 'integer', 'exists:unidades_medida,id'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'tipo_impuesto' => ['required', 'string', Rule::in(Producto::TIPOS_IMPUESTO_PERMITIDOS)],
            'porcentaje_iva' => ['required', 'numeric', 'min:0', 'max:100'],
            'codigo_impuesto' => ['nullable', 'string', 'max:30'],
            'permite_decimal' => ['required', 'boolean'],
            'codigo_barras' => ['nullable', 'string', 'max:50', 'unique:productos,codigo_barras'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tipo = $this->input('tipo_impuesto');
            $tarifa = $this->input('porcentaje_iva');

            if ($tipo === null || $tarifa === null || !is_numeric($tarifa)) {
                return;
            }

            $tarifaFloat = round((float) $tarifa, 2);

            if ($tipo === Producto::TIPO_IMPUESTO_GRAVADO && $tarifaFloat <= 0) {
                $validator->errors()->add(
                    'porcentaje_iva',
                    'Un producto clasificado como gravado debe tener una tarifa de IVA superior a 0%.'
                );
            }

            if (in_array($tipo, [Producto::TIPO_IMPUESTO_EXENTO, Producto::TIPO_IMPUESTO_EXCLUIDO], true) && $tarifaFloat != 0.0) {
                $validator->errors()->add(
                    'porcentaje_iva',
                    "Un producto clasificado como {$tipo} debe tener una tarifa de IVA igual a 0%."
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'El código ya está registrado.',
            'nombre.required' => 'El nombre es obligatorio.',
            'categoria_producto_id.required' => 'La categoría de producto es obligatoria.',
            'categoria_producto_id.exists' => 'La categoría de producto seleccionada no existe.',
            'unidad_medida_id.required' => 'La unidad de medida es obligatoria.',
            'unidad_medida_id.exists' => 'La unidad de medida seleccionada no existe.',
            'marca_id.exists' => 'La marca seleccionada no existe.',
            'precio_compra.numeric' => 'El precio de compra debe ser numérico.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser numérico.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'tipo_impuesto.required' => 'La clasificación tributaria del producto es obligatoria.',
            'tipo_impuesto.in' => 'La clasificación tributaria debe ser gravado, exento o excluido.',
            'porcentaje_iva.required' => 'La tarifa de IVA es obligatoria.',
            'porcentaje_iva.numeric' => 'La tarifa de IVA debe ser un valor numérico.',
            'porcentaje_iva.min' => 'La tarifa de IVA no puede ser negativa.',
            'porcentaje_iva.max' => 'La tarifa de IVA no puede superar el 100%.',
            'permite_decimal.required' => 'Debe indicar si el producto permite decimales.',
            'permite_decimal.boolean' => 'El campo permite_decimal debe ser verdadero o falso.',
            'codigo_barras.unique' => 'El código de barras ya está asociado a otro producto.',
        ];
    }
}