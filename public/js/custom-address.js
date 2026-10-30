(function(taylor, $, _) {
  taylor.CustomAddress = function(customShipAddress) {
    customShipAddress = customShipAddress || '{}';
    var legacyAddress;
    try {
      customShipAddress = JSON.parse(customShipAddress);
    } catch (e) {
      legacyAddress = String(customShipAddress);
      customShipAddress = parseTextAddress(customShipAddress);
    }

    this.countries = [
      {name: 'Canada', abbreviation: 'CAN'},
      {name: 'United States of America', abbreviation: 'USA'},
      {name: 'United Kingdom', abbreviation: 'UK'},
    ];

    this.provinces = [
      {name: 'Alberta', abbreviation: 'AB', population: 4_428_112},
      {name: 'British Columbia', abbreviation: 'BC', population: 5_145_851},
      {name: 'Manitoba', abbreviation: 'MB', population: 1_379_584},
      {name: 'New Brunswick', abbreviation: 'NB', population: 781_315},
      {name: 'Newfoundland and Labrador', abbreviation: 'NL', population: 520_998},
      {name: 'Northwest Territories', abbreviation: 'NT', population: 45_074},
      {name: 'Nova Scotia', abbreviation: 'NS', population: 979_115},
      {name: 'Nunavut', abbreviation: 'NU', population: 39_285},
      {name: 'Ontario', abbreviation: 'ON', population: 14_733_119},
      {name: 'Prince Edward Island', abbreviation: 'PE', population: 159_713},
      {name: 'Quebec', abbreviation: 'QC', population: 8_575_779},
      {name: 'Saskatchewan', abbreviation: 'SK', population: 1_177_884},
      {name: 'Yukon', abbreviation: 'YT', population: 42_176},
    ];

    this.states = [
      {name: 'Alabama', abbreviation: 'AL'},
      {name: 'Alaska', abbreviation: 'AK'},
      {name: 'American Samoa', abbreviation: 'AS'},
      {name: 'Arizona', abbreviation: 'AZ'},
      {name: 'Arkansas', abbreviation: 'AR'},
      {name: 'California', abbreviation: 'CA'},
      {name: 'Colorado', abbreviation: 'CO'},
      {name: 'Connecticut', abbreviation: 'CT'},
      {name: 'Delaware', abbreviation: 'DE'},
      {name: 'District Of Columbia', abbreviation: 'DC'},
      {name: 'Federated States Of Micronesia', abbreviation: 'FM'},
      {name: 'Florida', abbreviation: 'FL'},
      {name: 'Georgia', abbreviation: 'GA'},
      {name: 'Guam', abbreviation: 'GU'},
      {name: 'Hawaii', abbreviation: 'HI'},
      {name: 'Idaho', abbreviation: 'ID'},
      {name: 'Illinois', abbreviation: 'IL'},
      {name: 'Indiana', abbreviation: 'IN'},
      {name: 'Iowa', abbreviation: 'IA'},
      {name: 'Kansas', abbreviation: 'KS'},
      {name: 'Kentucky', abbreviation: 'KY'},
      {name: 'Louisiana', abbreviation: 'LA'},
      {name: 'Maine', abbreviation: 'ME'},
      {name: 'Marshall Islands', abbreviation: 'MH'},
      {name: 'Maryland', abbreviation: 'MD'},
      {name: 'Massachusetts', abbreviation: 'MA'},
      {name: 'Michigan', abbreviation: 'MI'},
      {name: 'Minnesota', abbreviation: 'MN'},
      {name: 'Mississippi', abbreviation: 'MS'},
      {name: 'Missouri', abbreviation: 'MO'},
      {name: 'Montana', abbreviation: 'MT'},
      {name: 'Nebraska', abbreviation: 'NE'},
      {name: 'Nevada', abbreviation: 'NV'},
      {name: 'New Hampshire', abbreviation: 'NH'},
      {name: 'New Jersey', abbreviation: 'NJ'},
      {name: 'New Mexico', abbreviation: 'NM'},
      {name: 'New York', abbreviation: 'NY'},
      {name: 'North Carolina', abbreviation: 'NC'},
      {name: 'North Dakota', abbreviation: 'ND'},
      {name: 'Northern Mariana Islands', abbreviation: 'MP'},
      {name: 'Ohio', abbreviation: 'OH'},
      {name: 'Oklahoma', abbreviation: 'OK'},
      {name: 'Oregon', abbreviation: 'OR'},
      {name: 'Palau', abbreviation: 'PW'},
      {name: 'Pennsylvania', abbreviation: 'PA'},
      {name: 'Puerto Rico', abbreviation: 'PR'},
      {name: 'Rhode Island', abbreviation: 'RI'},
      {name: 'South Carolina', abbreviation: 'SC'},
      {name: 'South Dakota', abbreviation: 'SD'},
      {name: 'Tennessee', abbreviation: 'TN'},
      {name: 'Texas', abbreviation: 'TX'},
      {name: 'Utah', abbreviation: 'UT'},
      {name: 'Vermont', abbreviation: 'VT'},
      {name: 'Virgin Islands', abbreviation: 'VI'},
      {name: 'Virginia', abbreviation: 'VA'},
      {name: 'Washington', abbreviation: 'WA'},
      {name: 'West Virginia', abbreviation: 'WV'},
      {name: 'Wisconsin', abbreviation: 'WI'},
      {name: 'Wyoming', abbreviation: 'WY'}
    ];

    this.regions = [].concat(this.provinces, this.states);

    this.$form = $(['<form class="custom-address">',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Attention</div>',
        '<input type="text" name="attention" placeholder="Attention" class="form-control" />',
      '</div>',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Country</div>',
        '<select type="text" name="country" class="form-control">',
          '<option>Country</option>',
          this.countries.map(function(country) {
            var selected = (customShipAddress['country'] == country.abbreviation) ? ' selected' : '';
            return '<option value="' + country.abbreviation + '"' + selected + '>' + country.name  + '</option>';
          }),
        '</select>',
      '</div>',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Company</div>',
        '<input type="text" name="company" placeholder="Company" class="form-control" />',
      '</div>',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Address 1</div>',
        '<input type="text" name="address1" placeholder="Address 1" class="form-control" />',
      '</div>',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Address 2</div>',
        '<input type="text" name="address2" placeholder="Address 2" class="form-control" />',
      '</div>',
      '<div class="form-group col-sm-6">',
        '<div class="font-semibold mb-2 px-2">City</div>',
        '<input type="text" name="city" placeholder="City" class="form-control" />',
      '</div>',
      '<div class="form-group col-sm-6">',
        '<div class="font-semibold mb-2 px-2">Province/State</div>',
        '<select name="provState" class="form-control">',
          '<option>Province/State</option>',
          this.regions.map(function(region) {
            var selected = (customShipAddress['provState'] == region.abbreviation) ? ' selected' : '';
            return '<option value="' + region.abbreviation + '"' + selected + '>' + region.abbreviation + ' - ' + region.name + '</option>';
          }),
        '</select>',
      '</div>',
      '<div class="form-group col-sm-12">',
        '<div class="font-semibold mb-2 px-2">Postal/Zip</div>',
        '<input type="text" name="postalZip" placeholder="Postal/Zip" class="form-control" />',
      '</div>',
    '</form>', (legacyAddress ? '<pre class="legacy-address">' + legacyAddress +  '</pre>' : '')].join(''));

    this.populateForm = function(customShipAddress) {
      var $form = this.$form;
      Object.keys(customShipAddress).forEach(function(field) {
        var value = customShipAddress[field];
        $form.find('[name="' + field + '"]').val(value);
      });
    }

    function parseTextAddress(textAddress) {
      if (!textAddress || !textAddress.match) {
        return {};
      }
      var matchAttention = /(?:Attention|Attn):?\s*(.+)/i;
      var attention = textAddress.match(matchAttention);
      if (attention) {
        textAddress = textAddress.replace(matchAttention, '').trim();
        attention = attention[1];
      }
      return { attention: attention };
    }

    this.populateForm(customShipAddress || {});
  }
}(globalThis.taylor, globalThis.jQuery, globalThis._));
