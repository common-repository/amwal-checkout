const path = require('path')
const defaults = require('@wordpress/scripts/config/webpack.config.js')

module.exports = {
  ...defaults,
  entry: {
    index: path.resolve(process.cwd(), 'assets/src'),
    'amwal-checkout': path.resolve(process.cwd(), 'assets/src/amwal-checkout.ts')
  },
  devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
  output: {
    filename: '[name].js',
    path: path.resolve(process.cwd(), 'assets/build')
  },
  externals: {
    libraryName: 'amwal-checkout-button'
  },
  module: {
    ...defaults.module,
    rules: [
      ...defaults.module.rules,
      {
        test: /\.tsx?$/,
        use: [
          {
            loader: 'ts-loader',
            options: {
              configFile: 'tsconfig.json',
              transpileOnly: true
            }
          }
        ]
      }
    ]
  },
  resolve: {
    extensions: ['.ts', '.tsx', ...(defaults.resolve ? defaults.resolve.extensions || ['.js', '.jsx'] : [])]
  }
}