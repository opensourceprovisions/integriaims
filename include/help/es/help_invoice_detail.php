<h1>Facturas</h1>
<p>
	De forma similar a los contratos, nos sirven para saber cuando hay facturas emitidas sin cobrar, y para saber cuando, y cuanto se ha facturado a una empresa determinada. El sistema se utiliza para gestionar las facturas EMITIDAS por nosotros, es decir, en ningún caso es un ERP que sirva para calcular ciclos de tesorería, gestión de impagos u otras características propias de un ERP. La gestión de facturas, que incluye la generación de facturas en PDF; está orientada desde el punto de vista de la gestión de Clientes, no de proveedores.
</p>
<p>
	En la versión <p>Enterprise</p> existe control de acceso a las facturas de una empresa. Tienen acceso a ellas, el propietario de la empresa y los usuarios que tengan perfil de gestor (CM). En la <b>versión</b> open, todo el mundo tiene acceso a las facturas.
</p>
<p>
	<?php print_image("images/help/company7.png", false, false); ?>
</p>
<p>
	<b>Las facturas contienen varios campos, de los cuales los más importante son:</b>
	<ul>
		<li><b>Identificación de la factura:</b> Número de factura. No puede estar duplicado con otra factura del sistema. Este sistema es únicamente para facturas emitidas por nuestra empresa, por lo que no debería ser posible tener dos facturas con el mismo ID.</li>
		<li><b>Referencia:</b> Generalmente para indicar un número de pedido, orden de compra o similar. Es opcional.</li>
		<li><b>Estado de la factura:</b> Pendiente de pago, pagada o anulada.</li>
		<li><b>Fecha de creación y fecha de pago efectiva.</b></li>
		<li><b>Concepto:</b> Hay cinco líneas, con cinco importes. Se mete por cada concepto el importe. Siempre sin impuestos, en bruto.</li>
		<li><b>Impuesto:</b> Indicar el %, p.e: 21 para 21%.</li>
		<li><b>Moneda:</b> EUR por defecto.</li>
		<li><b>Descripción:</b> Texto que saldrá en la factura, generalmente aclarando datos del pedido, añadiendo información adicional (como por ejemplo nº de cuenta para el pago, etc).</li>
	</ul>
</p>
<p>
	Una vez creada la factura, podemos adjuntar ficheros.
</p>
<p>
	<?php print_image("images/help/company8.png", false, false); ?>
</p>
<p>
	Un ejemplo de uso sería si es una factura generada por otro sistema y queremos guardar una imagen del archivo original de la factura aquí se puede adjuntar un fichero.
</p>
<p>
	<?php print_image("images/help/company9.png", false, false); ?>
</p>
<h2>Generación de IDs en facturas</h2>
<p>
	Esta utilidad nos permite generar IDs automáticamente a la hora de generar facturas. Para ello, activaremos la opción 'Enable auto ID' en la configuración de CRM como vemos en la siguiente captura:
</p>
<p>
	<?php print_image("images/help/company10.png", false, false); ?>
</p>
<p>
	En el campo Invoice ID pattern se guarda una cadena de texto que se utilizará como patrón para generar los IDs. Este patrón contendrá una parte fija y una variable. La parte variable debe ser numérica y servirá como primer elemento a partir del cual calcular una secuencia. La parte variable irá entre corchetes. Lo demás será constante en todas las facturas.
	Ejemplo de patrón: 15/[1000].
	</br>
	En este caso, las tres primeras facturas que se va a generar serán 15/1000, 15/1001 y 15/1002.
	</br>
	La generación de IDs de facturas se aplica únicamente en las facturas de tipo Enviado.
</p>
<h2>Bloqueo de facturas</h2>
<p>
	Una factura se puede bloquear -mediante el icono del candado- de forma que una vez bloqueada, no se puede modificar. Solo la puede modificar o borrar la persona que bloqueó la factura.
</p>