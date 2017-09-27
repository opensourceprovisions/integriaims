<h1>Condiciones de una regla de workflow</h1>
<p>
	Si pinchamos sobre la pestaña de Condiciones, accedemos al listado donde definiremos en que casos aplicaremos la regla, en nuestro ejemplo tenemos una condición ya definida. Podemos tener una o varias condiciones por regla. En el caso de que tengamos varias condiciones, con que se cumpla una, la regla se activará y ejecutará la acción o acciones programadas. Las condiciones están compuestas por un conjunto de filtros o subcondiciones, los cuales se describen a continuación:
	<ul>
		<li><b>Condición:</b> como se aplicarán al resto de los campos de los filtros dentro de la definición de la Condición (coincidan todos los campos, alguno o ninguno).</li>
		<li><b>Propietario:</b> responsable del ticket.</li>
		<li><b>Prioridad:</b> Una superior no engloba a las inferiores.</li>
		<li><b>Fecha de actualización:</b> Tiempo desde que el ticket fue actualizado por última vez.</li>
		<li><b>Texto a buscar en el título o descripción del incidente:</b> Busca una cadena dentro de la WU; el título o la descripción.</li>
		<li><b>Tarea:</b> Tarea asociada al ticket.</li>
		<li><b>Tipo de ticket:</b> Seleccionando el tipo de ticket, los campos personalizados aparecerán y podemos hacer uso de ellos para añadirlos al conjunto de filtros de esta Condición.</li>
		<li><b>Grupo</b> del ticket.</li>
		<li><b>Estado</b> del ticket.</li>
		<li><b>Fecha de creación:</b> Tiempo transcurrido desde la creación.</li>
		<li><b>SLA:</b> si el SLA fue disparada o no.</li>
		<li><b>Resolución</b> del ticket.</li>
		<li><b>Cumplimiento SLA (%):</b> porcentaje de cumplimiento del SLA del ticket.</li>
	</ul>
</p>
<p>
	<?php print_image("images/help/ticket41.png", false); ?>
</p>
<p>
	En esta condición de ejemplo hemos definido las características que debe cumplir el ticket. El grupo debe ser Soporte, la prioridad debe ser Media y el estado Asignado. El campo condición elegido es Coincidir todos los campos porque queremos que se cumplan todas. Si quisiéramos que se cumpliera alguna, el campo condición seleccionado sería Coincidir algún campo y si queremos que no se cumpla ninguna, el valor debe ser No hubo coincidencia.
</p>
<p>
	<?php print_image("images/help/ticket42.png", false, false); ?>
</p>

