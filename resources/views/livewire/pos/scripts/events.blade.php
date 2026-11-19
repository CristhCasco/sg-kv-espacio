
<script>
	document.addEventListener('DOMContentLoaded', function() {
		
		window.livewire.on('scan-ok', Msg => {			
			const text = String(Msg || '').toLowerCase();
			const isUpdated = text.includes('actualizada');
			noty(Msg, 1, {
				backgroundColor: isUpdated ? '#f4b400' : '#2e7d32'
			});
		})

		window.livewire.on('scan-notfound', Msg => {			
			noty(Msg, 2)
			doAction()
		})

		window.livewire.on('no-stock', Msg => {
			if (Msg && typeof Msg === 'object' && Msg.mode === 'restock') {
				doStockAction(Msg);
				return;
			}

			noty(Msg, 2, { backgroundColor: '#c1121f' })
		})

		window.livewire.on('low-stock', Msg => {
			noty(Msg, 2, {
				backgroundColor: '#ef6c00',
				actionText: 'VER TODAS',
				onActionClick: function() {
					window.livewire.emit('openLowStockModal');
				}
			});
		});

		window.livewire.on('sale-ok', Msg => {	
			console.log('sale-ok')	
		//@this.printTicket(Msg)		
		noty(Msg)			
		})

		window.livewire.on('sale-error', Msg => {			
			noty(Msg, 2)
		})

		window.livewire.on('show-print-options', (saleId) => {
			$('#printOptionsModal').modal('show');
		});

		window.livewire.on('show-low-stock-modal', () => {
			$('.modal').modal('hide');
			setTimeout(() => {
				$('.modal-backdrop').remove();
				$('body').removeClass('modal-open').css('padding-right', '');
				$('#lowStockModal').modal('show');
			}, 140);
		});

		window.addEventListener('close-modal', event => {
			const modalId = event.detail.modal;
			$('#' + modalId).modal('hide');
		});

		// Venta registrada como factura legal: se imprime como cualquier factura
		// (antes abría pos/factura, una ruta sin método que llevaba a una página de error)
		window.livewire.on('print-legal-invoice', (saleId) => {
			sgPrint(`{{ url('invoice-legal') }}/${btoa(String(saleId))}`);
		});

		window.addEventListener('close-all-modals-before-weight', () => {
			// Cierra cualquier modal Bootstrap abierto (ej: modal de búsqueda)
			// y elimina el backdrop para que el modal de peso sea interactivo
			$('.modal').modal('hide');
			setTimeout(() => {
				$('.modal-backdrop').remove();
				$('body').removeClass('modal-open').css('padding-right', '');
			}, 150);
		});

		window.addEventListener('focus-manual-weight-input', () => {
			setTimeout(() => {
				const input = document.getElementById('manualWeight');
				if (!input) return;
				input.focus();
				input.select();
			}, 40);
		});

		window.addEventListener('focus-out-stock-input', () => {
			setTimeout(() => {
				const input = document.getElementById('outStockQty');
				if (!input) return;
				input.focus();
				input.select();
			}, 40);
		});





		

		// METODO PROBADO
		//   window.livewire.on('print-json', b64 => {
		//   console.log('Print Data: ', b64);
		//   window.open("print://" + b64, '_self').close();
		//   })


		  //OTRO METODO
		  window.addEventListener('print-json', event => {   
        	console.log(event.detail.data);
        	silentMode(event.detail.data)
    	})

		let iframe = null
		function silentMode(sale){
			
			if(!iframe) {
			iframe = document.createElement('iframe')


				// atributos
				iframe.style.width = '0px'
				iframe.style.height = '0px'
				iframe.style.border = '0'
				document.body.appendChild(iframe)

			}
			iframe.src ="print://" + sale

		}



		 window.livewire.on('print-ticket', saleId => {	
		
		 if(getBrowser() !='edge'){
		 	window.open("print://" + saleId,  '_self').close()
		 } else {
		 	 window.open("print://" + saleId)
		 	//obj.close()
		 }

		})
		window.livewire.on('print-last-id', saleId => {					
			window.open("print://" + saleId,  '_self')
			//window.open("print://" + saleId,  '_self').close()//en chrome cierra la ventana
		})

		Livewire.on('venta-registrada', ventaHash => {
			const url = `{{ url('pos/ventas') }}/${ventaHash}/ticket`;
			if (typeof silentPrintPdf === 'function') {
				silentPrintPdf(url);
			} else {
				window.location.href = url;
			}
		});
		

	})
</script>


<script>
//console.log(window.navigator.userAgent.toLowerCase().indexOf("edge"))
function getBrowser(agent) {
	var agent = window.navigator.userAgent.toLowerCase()
	switch (true) {
		case agent.indexOf("edge") > -1: return "edge";
		case agent.indexOf("edg") > -1: return "chromium based edge (dev or canary)";
		case agent.indexOf("opr") > -1 && !!window.opr: return "opera";
		case agent.indexOf("chrome") > -1 && !!window.chrome: return "chrome";
		case agent.indexOf("trident") > -1: return "ie";
		case agent.indexOf("firefox") > -1: return "firefox";
		case agent.indexOf("safari") > -1: return "safari";
		default: return "other";
	}
}
  // console.log(getBrowser())
</script>
