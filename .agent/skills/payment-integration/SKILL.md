# Skill: Integración de Pasarelas de Pago y Tokenización

## Propósito
Implementar y auditar la integración segura de pasarelas de pago (Niubiz, Yape, Plin, Stripe, PayPal) e interacción con botones de pago y webhooks.

## Instrucciones para Antigravity
Cuando el usuario pida ayuda con flujos de cobro o pasarelas de pago:
1. Asegura que los scripts de la pasarela usen HTTPS y no expongan Llaves Privadas (Secret Keys) en el Frontend.
2. Valida la estructura de eventos JS para abrir modales de pago o redirecciones.
3. Configura o audita la lógica de respuesta (Response Tokens) y generación de comprobantes.