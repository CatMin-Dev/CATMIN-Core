<div class="modal fade" id="catmin-media-picker-modal" tabindex="-1" aria-labelledby="catmin-media-picker-title" aria-hidden="true"
     data-picker-endpoint="{{ admin_route('media.picker') }}"
     data-item-endpoint-template="{{ str_replace('/0/picker-item', '/__ID__/picker-item', admin_route('media.picker_item', ['asset' => 0])) }}">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="catmin-media-picker-title">Bibliotheque media</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body d-grid gap-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="catmin-media-picker-search">Recherche</label>
                        <input id="catmin-media-picker-search" type="search" class="form-control" placeholder="Nom, alt, legende, type...">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label" for="catmin-media-picker-kind">Type</label>
                        <select id="catmin-media-picker-kind" class="form-select">
                            <option value="">Tous</option>
                            <option value="image">Images</option>
                            <option value="document">Documents</option>
                            <option value="video">Videos</option>
                            <option value="audio">Audio</option>
                            <option value="other">Autres</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 d-flex gap-2">
                        <button type="button" class="btn btn-primary w-100" id="catmin-media-picker-run">
                            <i class="bi bi-search me-1"></i>Rechercher
                        </button>
                    </div>
                </div>

                <div id="catmin-media-picker-state" class="small text-muted"></div>
                <div id="catmin-media-picker-results" class="catmin-media-picker-grid"></div>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="catmin-media-picker-prev">Page precedente</button>
                    <span class="small text-muted" id="catmin-media-picker-page">Page 1</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="catmin-media-picker-next">Page suivante</button>
                </div>
            </div>
        </div>
    </div>
</div>
