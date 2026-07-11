@component('mail::message')
# ¡Bienvenido a Intermedius, {{ $user->name }}!

Tu cuenta ha sido creada. Para activarla, verifica tu correo electrónico:

@component('mail::button', ['url' => $url])
Verificar correo
@endcomponent

Este enlace expira en 24 horas.

Gracias,<br>
El equipo de Intermedius
@endcomponent
