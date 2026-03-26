// Cropper.js is loaded via direct script tag in header.php
// This adapter is kept for import map compatibility but just re-exports the global

if (typeof window !== 'undefined' && !window.Cropper) {
  throw new Error('Cropper library not found. Make sure it was loaded in header.php');
}

export default window.Cropper;
