const path = require('path');
const webpack = require('webpack');

// include the js minification plugin
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

// include the css extraction and minification plugins
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const OptimizeCSSAssetsPlugin = require("optimize-css-assets-webpack-plugin");

module.exports = {
    entry: ['./src/js/main.js', './src/scss/main.scss'],
    output: {
        filename: './static/main.js',
        path: path.resolve(__dirname)
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        presets: ['babel-preset-env']
                    }
                }
            },
            {
                test: /\.(sass|scss)$/,
                use: [
                    // {
                    //     loader: 'file-loader'
                    // },
                    {
                        loader: MiniCssExtractPlugin.loader
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            importLoaders: 2,
                            sourceMap: true
                        }
                    },
                    {
                        loader: 'sass-loader'
                    }
                ]
            },
            {
                test: /\.css$/i,
                use: [
                    {
                        loader: 'style-loader',
                    },
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: true,
                        }
                    },

                ]
            },
            {
                test: /\.(png|woff|woff2|eot|ttf|svg|gif)$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            // name: '[path][name].[ext]',
                            // publicPath: 'static/assets'
                            name: '[name].[ext]',
                          outputPath: 'static/assets/',
                          publicPath: 'static/assets/',
                          postTransformPublicPath: (p) => `__webpack_public_path__ + ${p}`,
                        }
                    }
                ]
            }

        ]
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery',
            'window.jQuery': 'jquery'
        }),
        new webpack.SourceMapDevToolPlugin({}),
        new MiniCssExtractPlugin({
            filename: './static/main.min.css'
        })
    ],
    optimization: {
        minimizer: [
            // enable the js minification plugin
            new UglifyJSPlugin({
                cache: true,
                parallel: true,
                uglifyOptions: {
                    output: {
                        comments: false,
                    },
                }
            }),
            // enable the css minification plugin
            new OptimizeCSSAssetsPlugin({})
        ]
    },
};
