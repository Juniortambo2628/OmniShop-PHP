@extends('layouts.admin')

@section('title', $product->exists ? 'Edit Product' : 'Add New Product')

@section('content')
<div class="admin-topbar">
    <h1>{{ $product->exists ? 'Edit Product: ' . $product->name : 'Add Custom Product' }}</h1>
    <div class="topbar-right">
        <a href="{{ route('admin.products') }}" class="btn btn-outline">Back to Products</a>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        Product Details
        @if($product->is_override && !$product->exists)
            <span class="badge badge-warning" style="margin-left: 10px;">Overriding Standard Catalog Item</span>
        @endif
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $product->exists ? route('admin.products.edit', $product->id) : route('admin.products.create') }}">
            @csrf

            <input type="hidden" name="is_override" value="{{ $product->is_override ? '1' : '0' }}">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Product ID (Internal)</label>
                    <input type="text" name="prod_id" class="form-control" value="{{ old('prod_id', $product->prod_id) }}" 
                           {{ ($product->exists || $product->is_override) ? 'readonly style=background:#eee;' : 'required' }}>
                    @error('prod_id')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
                </div>
                
                <div class="form-group">
                    <label>Product Code (Display)</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $product->code) }}" required>
                    @error('code')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                @error('name')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">-- Select Category --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat['id'] }}" {{ old('category_id', $product->category_id) == $cat['id'] ? 'selected' : '' }}>
                            {{ $cat['icon'] }} {{ $cat['name'] }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label>Price (Numeric)</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                    @error('price')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
                </div>
                
                <div class="form-group">
                    <label>Price Display String</label>
                    <input type="text" name="price_display" class="form-control" value="{{ old('price_display', $product->price_display) }}" required>
                    <small style="color: #6E6E6E;">e.g., "$150" or "POA"</small>
                    @error('price_display')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Dimensions / Specifications</label>
                <input type="text" name="dimensions" class="form-control" value="{{ old('dimensions', $product->dimensions) }}">
                @error('dimensions')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Colours (Comma separated)</label>
                @php
                    $colorsArray = old('colors', '');
                    if (!$colorsArray && !empty($product->colors_json)) {
                        $colorsArray = implode(', ', array_column($product->colors_json, 'name'));
                    }
                @endphp
                <input type="text" name="colors" class="form-control" value="{{ $colorsArray }}" placeholder="e.g., White, Black, Red">
                <small style="color: #6E6E6E;">Leave blank if not applicable.</small>
                @error('colors')<div style="color: red; font-size: 11px;">{{ $message }}</div>@enderror
            </div>

            <div style="display: flex; gap: 24px; margin-top: 16px;">
                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                    <label for="is_active" style="margin: 0; text-transform: none;">Active (Visible on Storefront)</label>
                </div>

                <div class="form-group" style="display: flex; align-items: center; gap: 8px;">
                    <input type="hidden" name="is_poa" value="0">
                    <input type="checkbox" name="is_poa" value="1" id="is_poa" {{ old('is_poa', $product->is_poa) ? 'checked' : '' }} style="width: 18px; height: 18px;">
                    <label for="is_poa" style="margin: 0; text-transform: none;">Price on Application (POA)</label>
                </div>
            </div>

            <div style="margin-top: 32px; border-top: 1px solid #eee; padding-top: 20px;">
                <button type="submit" class="btn btn-primary">
                    {{ $product->exists ? 'Save Changes' : 'Create Product' }}
                </button>
                <a href="{{ route('admin.products') }}" class="btn btn-outline" style="margin-left: 12px;">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
