document.addEventListener('DOMContentLoaded', function () {
    function toggleDetails(event) {
        event.preventDefault();
        const detailsContent = document.getElementById('detailsContent');
        detailsContent.style.display = detailsContent.style.display === 'none' ? 'block' : 'none';
    }

    const detailsLink = document.getElementById('detailsLink');
    detailsLink.addEventListener('click', toggleDetails);
});