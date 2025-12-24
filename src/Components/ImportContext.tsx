import React, { createContext, useContext, useState } from 'react';

interface ImportContextType {
    file: File | null;
    filename: string | null;
    setFile: (file: File | null) => void;
}

const ImportContext = createContext<ImportContextType | undefined>(undefined);

export const useImportContext = () => {
    const context = useContext(ImportContext);
    if (!context) {
        throw new Error('useImportContext must be used within ImportProvider');
    }
    return context;
};

interface ImportProviderProps {
    children: React.ReactNode;
}

export const ImportProvider: React.FC<ImportProviderProps> = ({ children }) => {
    const [file, setFile] = useState<File | null>(null);
    const [filename, setFilename] = useState<string | null>(null);

    const handleSetFile = (newFile: File | null) => {
        setFile(newFile);
        setFilename(newFile ? newFile.name : null);
    };

    return (
        <ImportContext.Provider value={{ file, filename, setFile: handleSetFile }}>
            {children}
        </ImportContext.Provider>
    );
};

