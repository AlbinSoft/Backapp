/*
import { useState } from "react";

const { data, setData } = useState(initPlaces.data);

const ctx = {
	data,
	setData,
	addPlace: info => {
console.log('addPlace', info);
		const data = [...this.data];
		data.push(info);
		this.setData(data);
	},
}

const PlacesContext = React.createContext(ctx);
*/

const PlacesContext = React.createContext({});

export default PlacesContext;
