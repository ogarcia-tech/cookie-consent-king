# Cookie Consent King

## Herramientas de prueba manual

Las nuevas herramientas están disponibles en **Consent King → Banner Settings → Testing tools**.

- **Force banner display** activa `forceShow` y obliga a que el banner se muestre aunque exista una cookie previa de consentimiento.
  Útil para validar estilos o traducciones sin limpiar el navegador entre pruebas.
- **Enable debug logs** activa `debug` y hace que el banner imprima mensajes descriptivos en la consola del navegador para seguir el flujo de eventos (renderizado, cambios de preferencias y guardado de decisiones).
- **Test button text / Test instructions URL** permiten ajustar el botón visible “Limpiar y Probar” dentro del banner. Al pulsarlo se eliminan las cookies/localStorage de consentimiento y se reconstruye el banner para repetir las pruebas. Si se define una URL opcional, aparece el enlace “Abrir documentación” junto al botón para acceder rápidamente a guías internas.

### Uso rápido para QA

1. Active **Force banner display** y **Enable debug logs** antes de comenzar una sesión de QA.
2. Recargue la página y utilice el botón **Limpiar y Probar** para reiniciar el flujo de consentimiento tantas veces como sea necesario.
3. Consulte la consola del navegador para revisar los mensajes de depuración mientras acepta, rechaza o personaliza preferencias.
4. Desactive los flags cuando finalicen las pruebas para restaurar el comportamiento normal del banner.
