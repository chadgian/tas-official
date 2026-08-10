// Create a new instance of Instascan
let scanner = new Instascan.Scanner({ video: document.getElementById('scanner') });

// Add a listener for when a QR code is scanned
scanner.addListener('scan', function(content) {
    alert('Scanned: ' + content);
});

// Start the scanner
Instascan.Camera.getCameras().then(function(cameras) {
if (cameras.length > 0) {
    alert("Success!");
    scanner.start(cameras[0]);
} else {
    alert("No Success 1!");
    console.error('No cameras found.');
}
}).catch(function(error) {
    alert("No Success 2! "+error);
    console.error(error);
});

// Quagga.init({
// inputStream : {
//     name : "Live",
//     type : "LiveStream",
//     target: document.querySelector('#scanner-container'),
//     constraints: {
//     facingMode: 'environment' // Use rear camera
//     }
// },
// decoder : {
//     readers : ['ean_reader', 'ean_8_reader', 'code_39_reader', 'code_39_vin_reader', 'codabar_reader', 'upc_reader', 'upc_e_reader', 'i2of5_reader', '2of5_reader', 'code_93_reader', 'code_128_reader', 'ean_13_reader', 'qrcode_reader']
// }
// }, function(err) {
//     if (err) {
//         alert("error - "+err);
//         console.log(err);
//         return
//     }
//     console.log("Initialization finished. Ready to start");
//     Quagga.start();
// });

// // Listen for a barcode scan
// Quagga.onDetected(function(data) {
// console.log("Barcode detected and processed: ", data);
// alert("QR Code scanned: " + data.codeResult.code);
// });