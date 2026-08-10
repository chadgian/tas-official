// script.js file
let qrCodeScanned = false;
let previousData = "";
setStatus("ready");

function domReady(fn) {
	if (
		document.readyState === "complete" ||
		document.readyState === "interactive"
	) {
		setTimeout(fn, 1000);
	} else {
		document.addEventListener("DOMContentLoaded", fn);
	}
}

let htmlscanner = new Html5QrcodeScanner(
	"my-qr-reader",
	{ fps: 50, qrbos: 250 }
);

function setStatus(status){
	const statusElement = document.getElementById('status');

	switch (status) {
		case "ready":
			statusElement.innerHTML = 'Ready to Scan!';
		break;
		case "decrypting":
			statusElement.innerHTML = 'Decrypting data...';
		break;
		case "recording":
			statusElement.innerHTML = 'Recording data...';
		break;
		default:
			statusElement.innerHTML = 'Error. Refresh the page.';        
		break;
	}
}

domReady(function () {
	// If found you qr code

	async function onScanSuccess(decodeText, decodeResult) {
		// alert("Scanned data: "+decodeText);
		if (!qrCodeScanned) { // Check if QR code has already been scanned
			qrCodeScanned = true;	

			// alert(previousData + " -- "+ decodeText);

			if(previousData === decodeText){
				qrCodeScanned = false;
				return;
			} 
			else {
				previousData = decodeText;
				setStatus("decrypting");
					
				const trainingID = document.getElementById("training").value;
				const days = document.getElementById("days").value;
				const inORout = document.getElementById("inORout").value;

				try {
					const fetchDecryptOption = {
						method: 'POST',
						headers: {
								'Content-Type': 'application/json'
						},
						body: JSON.stringify({data: decodeText})
					};

					setStatus("recording");
					const fetchDecrypt = await fetch('../processes/decrypt.php', fetchDecryptOption);
					const decyptedData = await fetchDecrypt.text();

					// alert(decyptedData);
					let scannedTrainingID = decyptedData.split(':')[0];

					if (scannedTrainingID === trainingID){
						const recordData = {
							result: decyptedData,
							trainingID: trainingID,
							days: days,
							inORout: inORout
						};
						
						const fetchRecordDataOption = {
							method: 'POST',
							headers: {
									'Content-Type': 'application/json'
							},
							body: JSON.stringify(recordData)
						}
		
						const fetchRecordData = await fetch('../processes/attendanceProcess.php', fetchRecordDataOption);
						const recordStatus = await fetchRecordData.text();

						if (recordStatus === "success"){
							var audio = new Audio('../sources/success.wav');
							audio.play();
							setStatus("ready");
							qrCodeScanned = false;
						} else {
							alert(recordStatus);
						}

					} else {
						alert("Wrong training!");
						qrCodeScanned = false;
					}

				} catch (error) {
					
				}
			}

		}
	}

	// if (counter === 0){
	// 	counter++;
	// 	htmlscanner.render(onScanSuccess);
	// }

	// counter++;
	htmlscanner.render(onScanSuccess);
});
