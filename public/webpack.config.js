const path = require('path');

module.exports = {
  mode: 'production',
  entry: './node_modules/chrono-node/dist/esm/index.js',  // path to chrono-node
  output: {
    filename: 'chrono-bundle.js',  // the name of the bundled output file
    path: path.resolve(__dirname, 'bundles'),  // directory where the output file should be stored
    library: 'chrono',  // the name of the global variable under which the library will be accessible
    libraryTarget: 'umd',  // makes the library compatible with different environments (e.g., CommonJS, AMD, global)
  },
};
