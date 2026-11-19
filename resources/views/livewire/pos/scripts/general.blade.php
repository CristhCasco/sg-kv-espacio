
<script>
	document.addEventListener('DOMContentLoaded', function() {
		$('.tblscroll').niceScroll({
			cursoscolor: "#515365",
			cursorwidth: "5px",
			background: "rgba(20,20,20,0.3)",
			cursorborder: "0px",
			cursorborderradius: 3

		})


		// cuando la modal se haya cargado, ponemos focus en el input search
		$('#modalSearchProduct').on('shown.bs.modal', function() {
			$('#modal-search-input').focus();
		})


		window.livewire.on('show-scroll', Msg => {	
			aplyNiceScrollDynamically()
			$('.tblscroll').niceScroll({
			cursoscolor: "#515365",
			cursorwidth: "5px",
			background: "rgba(20,20,20,0.3)",
			cursorborder: "0px",
			cursorborderradius: 3

		})

		})
		window.livewire.on('scan-ok', Msg => {	
		//console.log('aplyNiceScrollDynamically')		
			//aplyNiceScrollDynamically()
		})

		function aplyNiceScrollDynamically(argument) {
			$('.tblscroll').niceScroll({
				cursoscolor: "#515365",
				cursorwidth: "30px",
				background: "rgba(20,20,20,0.3)",
				cursorborder: "0px",
				cursorborderradius: 3

			})
		}
		

	})



	function Confirm(id, eventName, text) {
		swal({
			title: 'CONFIRMAR',
			text: text,
			type: 'warning',
			showCancelButton: true,
			cancelButtonText: 'Cerrar',
			cancelButtonColor: '#fff',
			confirmButtonColor: '#620408',
			confirmButtonText: 'Aceptar'
		}).then(function(result) {
			if (result.value) {
				window.livewire.emit(eventName, id)
				swal.close()
			}

		})
	}


	function doAction() {
		swal({
			title: '',
			text: '¿QUIERES REGISTRAR EL PRODUCTO?',
			type: 'warning',
			showCancelButton: true,
			cancelButtonText: 'NO',
			cancelButtonColor: '#fff',
			confirmButtonColor: '#620408',
			confirmButtonText: 'SI'
		}).then(function(result) {
			if (result.value) {
				//$('#theModal').modal('show')
				window.location = '{{ url('products') }}'
				swal.close()
			}

		})
	}

	function doStockAction(payload) {
		const productId = parseInt(payload?.productId || 0, 10);
		const productName = payload?.productName || 'Producto';
		const availableStock = Number(payload?.availableStock || 0);
		const requestedQty = parseInt(payload?.requestedQty || 1, 10);
		const retryAction = payload?.retryAction || 'addUnit';
		const targetQty = payload?.targetQty ?? null;

		if (!productId) {
			noty('Producto inválido para actualizar stock', 2);
			return;
		}

		swal({
			title: 'STOCK INSUFICIENTE',
			html:
				'<div style="text-align:left">' +
				'<div><strong>Producto:</strong> ' + productName + '</div>' +
				'<div><strong>Stock disponible:</strong> ' + availableStock + '</div>' +
				'<div style="margin-top:8px"><strong>Cantidad a agregar:</strong></div>' +
				'</div>',
			type: 'warning',
			input: 'number',
			inputValue: '',
			inputAttributes: {
				min: 1,
				step: 1
			},
			onOpen: () => {
				const input = swal.getInput();
				if (input) {
					input.focus();
				}
			},
			showCancelButton: true,
			cancelButtonText: 'CERRAR',
			confirmButtonColor: '#620408',
			confirmButtonText: 'ACTUALIZAR STOCK',
			preConfirm: (value) => {
				const qty = parseInt(value, 10);
				if (!qty || qty < 1) {
					swal.showValidationMessage('Ingresa una cantidad válida mayor a 0');
					return false;
				}
				return qty;
			}
		}).then(function(result) {
			if (!result.value) {
				return;
			}

			window.livewire.emit('updateStockFromNoStockModal', productId, result.value, requestedQty, retryAction, targetQty);
			swal.close();
		});
	}
	
</script>
