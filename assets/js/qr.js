document.querySelectorAll('[data-ar-qr]').forEach((element) => {
    if (!window.QRCode) {
        element.textContent = 'QR unavailable';
        return;
    }
    const url = new URL(element.dataset.arPath, window.location.origin).href;
    new window.QRCode(element, {
        text: url,
        width: 82,
        height: 82,
        colorDark: '#123d35',
        colorLight: '#ffffff',
        correctLevel: window.QRCode.CorrectLevel.M,
    });
});
