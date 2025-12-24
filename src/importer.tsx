import React from 'react';
import ImportHeader from './Components/importheader';
import TwoPanelLayout from './Components/TwoPanelLayout';
import { ImportProvider } from './Components/ImportContext';
import { createRoot } from 'react-dom/client';
import './styles/importer.scss';

function initializeImporter() {
    const importer = document.getElementById('ttbp-import-page');
    if (importer) {
        const root = createRoot(importer);
        root.render(
            <ImportProvider>
                <div className="importer-container">
                    <ImportHeader />
                    <TwoPanelLayout />
                </div>
            </ImportProvider>
        );
    }
}

initializeImporter();