const path = require('path');
const glob = require('glob');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyPlugin = require('copy-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

// Find all block directories
const blocks = glob.sync('./*/block.json', { cwd: __dirname }).map(blockPath => {
  const blockDir = path.dirname(blockPath);
  return blockDir;
});

// Create entry points for each block
const entry = {};
blocks.forEach(blockDir => {
  const blockName = path.basename(blockDir);
  entry[blockName] = path.resolve(__dirname, blockDir, 'src', 'index.js');
});

module.exports = {
  ...defaultConfig,
  entry,
  output: {
    ...defaultConfig.output,
    path: path.resolve(__dirname, '..', 'dist'),
    filename: '[name]/index.js',
  },
  plugins: [
    // Replace the default MiniCssExtractPlugin with our custom one
    ...defaultConfig.plugins.filter(
      plugin => !(plugin instanceof MiniCssExtractPlugin)
    ),
    new MiniCssExtractPlugin({
      filename: '[name]/style.css',
    }),
    // Copy block.json and render.php files to dist
    new CopyPlugin({
      patterns: blocks.flatMap(blockDir => {
        const blockName = path.basename(blockDir);
        const patterns = [
          {
            from: path.resolve(__dirname, blockDir, 'block.json'),
            to: path.resolve(__dirname, '..', 'dist', blockName, 'block.json'),
          },
        ];
        // Copy render.php if it exists
        const renderPhp = path.resolve(__dirname, blockDir, 'src', 'render.php');
        const fs = require('fs');
        if (fs.existsSync(renderPhp)) {
          patterns.push({
            from: renderPhp,
            to: path.resolve(__dirname, '..', 'dist', blockName, 'render.php'),
          });
        }
        return patterns;
      }),
    }),
  ],
};
