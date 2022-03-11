@component($typeForm, get_defined_vars())
<style>

#mappa {
  height: 400px;
  /* The height is 400 pixels */
  width: 100%;
  /* The width is the width of the web page */
  position: sticky !important;
}
#infowindow-content .title {
  font-weight: bold;
}

#infowindow-content {
  /* display: none; */
}

#map #infowindow-content {
  display: inline;
}

.pac-card {
  background-color: #fff;
  border: 0;
  border-radius: 2px;
  box-shadow: 0 1px 4px -1px rgba(0, 0, 0, 0.3);
  margin: 10px;
  padding: 0 0.5em;
  font: 400 18px Roboto, Arial, sans-serif;
  overflow: hidden;
  font-family: Roboto;
  padding: 0;
}

#pac-container {
  padding-bottom: 12px;
  margin-right: 12px;
}

.pac-controls {
  display: inline-block;
  padding: 5px 11px;
}

.pac-controls label {
  font-family: Roboto;
  font-size: 13px;
  font-weight: 300;
}

#pac-input {
  background-color: #fff;
  font-family: Roboto;
  font-size: 15px;
  font-weight: 300;
  margin-left: 12px;
  padding: 0 11px 0 13px;
  text-overflow: ellipsis;
  width: 400px;
}

#pac-input:focus {
  border-color: #4d90fe;
}

#title {
  color: #fff;
  background-color: #4d90fe;
  font-size: 25px;
  font-weight: 500;
  padding: 6px 12px;
}
</style>
<script>
  function initMap() {
    var myStyles =[
    {
        featureType: "poi",
        elementType: "labels",
        stylers: [
              { visibility: "off" }
        ]
    }
];
  const defaultLocation =  { lat: 41.22243904039791, lng: 15.387747287750246 }; 
  const mappa = new google.maps.Map(document.getElementById("mappa"), {
    center: defaultLocation,
    zoom: 19,
    mapTypeControl: false,
    mapTypeId: google.maps.MapTypeId.ROADMAP,
    streetViewControl: false,
    styles: myStyles
  });
  const input = document.getElementById("{{$attributes['id']}}");
  const options = {
    componentRestrictions: { country: "it" },
    fields: ["formatted_address", "geometry", "name"],
    strictBounds: false,
    types: ["geocode"],
  };

  const autocomplete = new google.maps.places.Autocomplete(input, options);

  autocomplete.bindTo("bounds", mappa);
  autocomplete.setTypes(["geocode"]);
  autocomplete.setComponentRestrictions({
    country: ["it"],
  });
  const infowindow = new google.maps.InfoWindow();
  const infowindowContent = document.getElementById("infowindow-content");

  infowindow.setContent(infowindowContent);

  var marker = new google.maps.Marker({
    mappa,
    anchorPoint: new google.maps.Point(0, -29),
  });

  autocomplete.addListener("place_changed", () => {
    infowindow.close();
    marker.setVisible(false);

    const place = autocomplete.getPlace();

    if (!place.geometry || !place.geometry.location) {
      window.alert("Nessuna località disponibile per: '" + place.name + "'");
      return;
    } else {
      console.log({ address: place.formatted_address, lat: place.geometry.location.lat(), lng: place.geometry.location.lng()});
          // If the place has a geometry, then present it on a map.

    var Latlng_0 = new google.maps.LatLng(place.geometry.location.lat(),place.geometry.location.lng());

    marker = new google.maps.Marker(
        {
            position: Latlng_0, 
            title:"0"
        }
    );

    marker.setMap(mappa);

    mappa.setCenter(place.geometry.location);
    infowindowContent.children["place-icon"].src = place.icon;
    infowindowContent.children["place-name"].textContent = place.name;
    infowindowContent.children["place-address"].textContent = place.address;
    infowindow.open(mappa, marker);

    document.getElementsByName("{{$name}}[lat]")[0].value=place.geometry.location.lat();
    document.getElementsByName("{{$name}}[lng]")[0].value=place.geometry.location.lng();
;


    }


  });
}
</script>


<div data-controller="input"
data-input-mask="{{$mask ?? ''}}" id="pac-container"
>

@php
$nfield  = $attributes['name'] . "[nome]";
$nomeattr = $attributes;
unset($nomeattr['name']);

@endphp

<input {{ $nomeattr }} name="{{$nfield}}">
</div>

<div id="mappa"></div>
<div id="infowindow-content">
  <img src="" width="16" height="16" id="place-icon" />
  <span id="place-name" class="title"></span><br />
  <span id="place-address"></span>
</div>
<div class="row mt-2">
  <div class="col-md">
      <label for="{{$name}}[lat]">{{ __('Latitude') }}</label>
      <input class="form-control"
             id="marker__latitude"
             data-map-target="lat"
             @if($required ?? false) required @endif
             name="{{$name}}[lat]"
             value="{{ $value['lat'] ?? '' }}"/>
  </div>
  <div class="col-md">
      <label for="{{$name}}[lng]">{{ __('Longitude') }}</label>
      <input class="form-control"
             id="marker__longitude"

             data-map-target="lng"
             @if($required ?? false) required @endif
             name="{{$name}}[lng]"
             value="{{ $value['lng'] ?? '' }}"/>
  </div>
</div>

@endcomponent

