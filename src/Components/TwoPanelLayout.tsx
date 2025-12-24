import React from 'react';
import { useImportContext } from './ImportContext';

const TwoPanelLayout: React.FC = () => {
    const { file, filename } = useImportContext();

    return (
        <div className="two-panel-layout">
            <div className="panel panel-left">
                {file ? (
                    <div className="panel-content">
                        <h2>Left Panel</h2>
                        <p>File loaded: {filename}</p>
                        {/* Add left panel content here */}
                    </div>
                ) : (
                    <div className="panel-placeholder">
                        <p>Upload a file to get started</p>
                    </div>
                )}
            </div>
            <div className="panel panel-right">
                {file ? (
                    <div className="panel-content">
                        <h2>Right Panel</h2>
                        <p>File loaded: {filename}</p>
                        {/* Add right panel content here */}
                    </div>
                ) : (
                    <div className="panel-placeholder">
                        <p>Upload a file to get started</p>
                    </div>
                )}
            </div>
        </div>
    );
}

export default TwoPanelLayout;

