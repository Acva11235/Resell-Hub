document.addEventListener('DOMContentLoaded', function() {
    const imageInput = document.getElementById('product_images');
    const previewContainer = document.getElementById('image-preview');
    const placeholder = previewContainer.querySelector('.preview-placeholder');

    if (imageInput && previewContainer && placeholder) {
        imageInput.addEventListener('change', function(event) {
            previewContainer.innerHTML = '';
            const files = event.target.files;
            if (files.length === 0) {
                previewContainer.appendChild(placeholder);
                return;
            }
            previewContainer.appendChild(placeholder);
            placeholder.style.display = 'none';

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const wrapper = document.createElement('div');
                        wrapper.classList.add('img-preview-wrapper');
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('img-preview');
                        img.alt = 'Image preview ' + (i + 1);
                        wrapper.appendChild(img);
                        previewContainer.appendChild(wrapper);
                    }
                    reader.readAsDataURL(file);
                } else {
                    const errorSpan = document.createElement('span');
                    errorSpan.textContent = `File '${file.name}' is not a valid image. `;
                    errorSpan.style.color = 'red';
                    errorSpan.style.fontSize = '0.9em';
                    previewContainer.appendChild(errorSpan);
                }
            }
            if (previewContainer.querySelectorAll('.img-preview-wrapper').length === 0 && previewContainer.querySelectorAll('span[style*="color: red"]').length === 0) {
                placeholder.style.display = 'block';
            }
        });
    }
});
