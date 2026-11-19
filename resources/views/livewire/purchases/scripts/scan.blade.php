
	<script>
		
		try{
		
			onScan.attachTo(document, {
			suffixKeyCodes: [13],
			// Lo que se escribe o escanea en el buscador de proveedores (ej: RUC) no es un producto
			ignoreIfFocusOn: '#modalSearchSupplier input',
			onScan: function(barcode) {
				console.log(barcode)
				window.livewire.emit('scan-code', barcode)
			},
			onScanError: function(e){
				console.log(e)
			}
		})
		
			console.log('LISTO PARA ESCANEAR')
		
		
		} catch(e){
			console.log('Error de lectura: ', e)
		}
		
		
	</script>
	
	
