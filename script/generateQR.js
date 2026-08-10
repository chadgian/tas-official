let nameList = document.getElementById("name").value;
let trainingName = document.getElementById("trainingName").value;
// console.log("First Names: " + nameList);
let nameArray = nameList.split(",");
console.log("Names: " + nameList);

if (nameArray.length > 0) {
    // Create a zip file
    let zip = new JSZip();

    // Generate QR codes for each name
    let promises = nameArray.map(eachName => {
        return new Promise((resolve, reject) => {
            let qrcodeContainer = document.createElement("div");
            new QRCode(qrcodeContainer, eachName);
            // new QRCode(qrcodeContainer, eachName.replace(/ñ/g, 'n'));


            // Convert the QR code image to a Blob
            qrcodeContainer.firstChild.toBlob(blob => {
                // Add the Blob to the zip file
                zip.file(eachName.replace(/[^a-zA-Z0-9]/g, '_') + ".png", blob);
                resolve();
            }, 'image/png');
        });
    });

    // Wait for all promises to resolve
    Promise.all(promises).then(() => {
        // Generate the zip file asynchronously
        zip.generateAsync({ type: "blob" }).then(function (content) {
            // Trigger download of the zip file
            saveAs(content, trainingName + "-qrcodes.zip");
            
            window.location.href = '../main.php';
        });
    });
} else {
    alert("Please enter a valid list of names");
}
