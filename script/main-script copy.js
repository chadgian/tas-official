// script.js file
let qrCodeScanned = false;

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

domReady(function () {
	// If found you qr code
	function onScanSuccess(decodeText, decodeResult) {
		// alert("Scanned data: "+decodeText);
		if (!qrCodeScanned) { // Check if QR code has already been scanned
			qrCodeScanned = true;

        fetch('../processes/decrypt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({data: decodeText})
        })
        .then(response => response.text())
        .then(data => {
					// alert("Decrypted data: "+data);

					let trainingID = document.getElementById('training').value;
					let scannedTrainingID = data.split(':')[0];
			
					if (trainingID !== scannedTrainingID){
						alert("Wrong training!");
					} else {

						var audio = new Audio('../sources/success.wav');
						audio.play();

						audio.addEventListener('ended', function() {
							let nameResult = document.getElementById("name-result");
							let nameLabel = document.getElementById('name-label');
							nameLabel.innerHTML = data.split(':')[-1];
							nameResult.value = data;
							
							let button = document.getElementById("saveButton");
							button.click();
						});
					}
        })
        .catch(error => console.error('Error:', error));
		}
	}

	// if (counter === 0){
	// 	counter++;
	// 	htmlscanner.render(onScanSuccess);
	// }

	// counter++;
	htmlscanner.render(onScanSuccess);
});
