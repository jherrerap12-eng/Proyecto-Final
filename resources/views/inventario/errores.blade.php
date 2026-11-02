@if ($errors->any())
    <div class="alert alert-danger" role="alert" tabindex="-1" id="errores-formulario">
        <p class="fw-bold mb-2">Revisa la información antes de continuar:</p>
        <ul class="mb-0">@foreach ($errors->all() as $mensaje)<li>{{ $mensaje }}</li>@endforeach</ul>
    </div>
@endif
