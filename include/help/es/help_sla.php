
<h1>Gestión de SLA</h1>
<p>
La SLA es la forma de “comprobar” que la gestión de tickets funciona bajo unos criterios. Las SLA se gestionan automáticamente por Integria, que las comprueba de forma periódica mediante la tarea programada que se activa cuando se instala Integria.
</p>
<p><b>El SLA se procesa conforme unos parámetros:</b>
	<ul>
		<li>    
			<b>Nombre:</b> Es el texto que aparecerá en los combos de selección para identificar el SLA.
		</li> 
		<li>
			<b>Enforced:</b> Hace que la SLA dispare los emails cuando se imcumpla (enforced) o que solo avise con un indicador luminoso. 
		</li>
		<li>    
			<b>SLA Base:</b> Indica que la SLA está relacionada con otra (solo a nivel informativo).
		</li>
		<li>    
			<b>Tipo SLA:</b> El SLA puede ser de tres tipos:
			<ul>
				<li><b>SLA Normal:</b> Se tendrán en cuenta, a la hora de hacer el cálculo, los tickets que no se encuentren en estado Cerrado o Pendiente de terceros.</li>
				<li><b>SLA de terceros:</b> Se tendrán en cuenta los tickets que estén en estado Pendiente de terceros.</li>
				<li><b>Ambos:</b> Se tendrán en cuenta los tickets que estén en cualquier estado que no sea Cerrado.</li>
			</ul>
		</li>
		<li>    
			<b>Max. Tiempo de respuesta:</b> Indica en horas, el mínimo tiempo de respuesta que tiene que haber entre una notificación (ticket nueva o UT) del creador del ticket. Pasado ese tiempo, se disparará la SLA. Por ejemplo, si es 4 horas, y un ticket nueva tiene 4.1 horas de vida, se disparará la SLA. Si por ejemplo es un ticket viejo (1 semana) y la última UT es del creador del ticket, y tiene maś de 4 horas, también se disparará el ticket.
		</li>
			<li>    
			<b>Max. Tiempo de resolución:</b> Indica en horas, el máximo tiempo de vida de un ticket. Si un ticket tiene más de ese tiempo y no está cerrado o resulto, saltará la SLA.
		</li>
		<li>    
			<b>Nº Máx de tickets abiertos al mismo tiempo:</b> Indica el nº total de tickets que pueden estar abiertas simultáneamente. Si hay más, saltará la SLA.
		</li>
		<li>    
			<b>Max. Tiempo de inactividad: </b> Define el tiempo máximo de inactividad del ticket. Cuando este tiempo se supere se enviará un email al responsable para recordarle que el ticket está abierto.
		</li>
		<li>    
			<b>Hora de comienzo para activar la SLA:</b> Hora a partir de la cual la SLA se empieza a calcular (p.e: 9 de la mañana).
		</li>
		<li>    
			<b>Hora de fin para una SLA:</b> Hora a partir de la cual la SLA ya no se calcula (p.e: 18 hr).
		</li>
		<li>    
			<b>Deshabilitar SLA en fines de semana:</b> Si esta opción está activada el SLA sólo se calcula los días entre semana, los fines de semana quedarían excluidos.
		</li>
		<li>    
			<b>Deshabilitar SLA en vacaciones:</b> Si esta opción está activada el SLA no se calculará los días definidos como días de vacaciones.
		</li>
		<li>    
			<b>Descripción:</b> Texto informativo para describir la SLA.
		</li>	
	</ul>
</p>
<h1> ¿Qué significa "saltará el SLA"? </h1>
<p>
	Significa que el sistema enviará una notificación por email al propietario del ticket, advirtiendo que el ticket no cumple los baremos establecidos en la SLA a la que pertenece el ticket. Un ticket puede estar sujeto a diferentes SLA simultáneamente, si está asociado a diferentes objetos de inventario. Un SLA que ya ha saltado no se desactiva sola por el hecho de estar en fin de semana, es decir, aquellos tickets que no cumplen SLA, hasta que no cumpla todas las condiciones, no volverá a estado “normal”.
</p>
<p>
	Podemos ver la evolución histórica del cumplimiento de SLA de un ticket, o los valores de cumplimiento en total en los informes generales. Una SLA baja, generalmente significa que el ticket no se ha gestionado bien. Es un indicador muy usado para ofrecer un resumen de la calidad de la gestión del ticket.
</p>
<p>
	Cuando una SLA salta, un indicador luminoso aparece en la vista de tickets.
</p>
<p>
	Veamos un ejemplo de definición de SLA:
</p>
<p>
	<?php print_image("images/help/ticket37.png", false); ?>
</p>
<p>
	Las SLAs están vinculadas al estado de los tickets. De forma que si el tipo de SLA elegido es Normal <b> no se aplicará el SLA para los tickets que están en estados Cerrado y Pendiente de terceras personas</b>. Si el tipo elegido es SLA de terceros <b>sólo se aplicará el SLA para los tickets que estén en estado Pendiente de terceras personas.</b> Si se eligen Ambas <b>se aplicará el SLA para todos los tickets excepto los que estén en estado Cerrado.</b>
</p>
<h1>Evaluación de un ticket por su SLA</h1>
<p>
	Utilizando el sistema de SLA, y en el informe de “seguimiento” de un ticket, podemos ver, en una escala de tiempo, cuando el ticket no ha cumplido (en rojo) y cuando ha cumplido (en verde). Además de un indicador del % de cumplimiento del ticket en toda la vida de éste.
</p>
<p>
	<?php print_image("images/help/ticket38.png", false); ?>
</p>