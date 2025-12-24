import React, { useRef } from 'react';
import { useImportContext } from './ImportContext';

const ImportHeader: React.FC = () => {
    const { filename, setFile } = useImportContext();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleUploadClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = event.target.files?.[0];
        if (selectedFile) {
            setFile(selectedFile);
        }
    };

    return (
        <div className="import-header">
            <div className="import-header-content">
                <h1>Book Importer</h1>
                <div className="import-header-actions">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept=".epub,.mobi,.pdf"
                        onChange={handleFileChange}
                        style={{ display: 'none' }}
                    />
                    <button onClick={handleUploadClick} className="upload-button">
                        {filename ? `Change File (${filename})` : 'Upload File'}
                    </button>
                    {filename && (
                        <span className="filename-display">{filename}</span>
                    )}
                </div>
            </div>
        </div>
    );
}

export default ImportHeader;