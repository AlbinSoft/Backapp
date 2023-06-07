const path = require('path');

module.exports = {
	entry: path.join(__dirname, 'index.jsx'),
	output: {
		path: path.join(__dirname),
		filename: 'index.js'
	},
	mode: 'development', // process.env.NODE_ENV ||
	devtool: 'inline-source-map',
	resolve: {
		modules: [path.resolve(__dirname), 'node_modules']
	},
	devServer: {
		contentBase: path.join(__dirname),
		port: 3500,
		watchContentBase: true,
		open: true
	},
	module: {
		rules: [
			{
				test: /\.(jsx)$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader"
				}
			},
			{
			//	test: /\.(css)$/,
			//	exclude: /node_modules/,
			//	use: ["style-loader", "css-loader"]
				test: /\.css$/,
				use: [
					"style-loader",
					{
						loader: "css-loader?modules=true&camelCase=true",
						options: {
							importLoaders: 1,
							modules: true,
						},
					}
				]
			}
		],
	},
	resolve: {
		extensions: ['.js', '.json', '.jsx', '.css'],
		modules: [
			path.resolve(__dirname, 'src'),
			'node_modules'
		]
	},
	watch: true,
};