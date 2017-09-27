<h1>Importar datos de inventario desde CSV</h1>
<p>
	Existe la opción de importar datos de inventario desde un fichero CSV. Para ello, se carga el fichero con una línea por inventario y los valores separados por comas. El orden debe ser el siguiente:
</p>
<p>
	ID tipo de objeto, Propietario, Nombre, Público, Descripción, Contrato, ID Fabricante, ID Padre, Compañía asociada1;…;Compañía asociadaN,UsuarioAsociado1;;UsuarioAsociadoN,Estado,Campo1 del tipo de objeto,…,CampoN del tipo de objeto
</p>
<p>
	Los estados que puede tomar un inventario son:
	</br>
	</br>
	<ul>
		<li><b>new:</b> Nuevo.</li>
		<li><b>inuse:</b> En uso</li>
		<li><b>unused:</b> En desuso.</li>
		<li><b>issued:</b> Borrado.</li>
	</ul>
</p>
<p>
	<i><b>Ejemplo:</b> 19,admin,Inventario de prueba,1,describiendo inventario,5,1,0,6;7,admin;user,inuse,Linux,CentOS</i>
</p>
