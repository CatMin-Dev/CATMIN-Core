// Form Upload.html specific JavaScript with Uppy integration (jQuery-free)

// Bootstrap 5
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;
globalThis.bootstrap = bootstrap;

// Global styles

// Essential scripts for layout
import './helpers/smartresize.js';
import './sidebar.js';
import './init.js';

// Uppy for file uploads (modern replacement for Dropzone)
import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import XHRUpload from '@uppy/xhr-upload';

// Import Uppy CSS

// Make Uppy available globally
window.Uppy = Uppy;
globalThis.Uppy = Uppy;

// Initialize Uppy when DOM is ready
function initUploadPage() {
  const uploadContainer = document.querySelector('.uppy-upload');

  if (uploadContainer) {
    try {
      const uppy = new Uppy({
        debug: false,
        autoProceed: false,
        restrictions: {
          maxFileSize: 20 * 1024 * 1024, // 20MB
          allowedFileTypes: [
            'image/*',
            'application/pdf',
            '.psd',
            '.doc',
            '.docx',
            '.xls',
            '.xlsx',
            '.ppt',
            '.pptx'
          ]
        }
      });

      uppy.use(Dashboard, {
        inline: true,
        target: '.uppy-upload',
        width: '100%',
        height: 400,
        showProgressDetails: true,
        proudlyDisplayPoweredByUppy: false,
        theme: 'light',
        note: 'Images, PDFs, and Office documents up to 20 MB'
      });

      // For demo purposes - use XHRUpload with a dummy endpoint
      // In production, replace with your actual upload endpoint
      uppy.use(XHRUpload, {
        endpoint: '#',
        formData: true,
        fieldName: 'file'
      });

      // Event handlers
      uppy.on('file-added', (file) => {
        if (false) {
          console.log('File added:', file.name);
        }
      });

      uppy.on('upload-success', (file, _response) => {
        if (false) {
          console.log('Upload success:', file.name);
        }
      });

      uppy.on('complete', (result) => {
        if (false) {
          console.log('Upload complete:', result.successful.length, 'files uploaded');
        }
      });

      // Store reference globally
      window.uppy = uppy;
      globalThis.uppy = uppy;

      if (false) {
        console.log('Uppy initialized successfully');
      }
    } catch (error) {
      if (false) {
        console.error('Uppy initialization error:', error);
      }
    }
  }
}

window.initUploadPage = initUploadPage;
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initUploadPage);
} else {
  initUploadPage();
}
