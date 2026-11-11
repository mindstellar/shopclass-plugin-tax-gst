# Changelog

## 0.2.1

### Changed
- On Shopclass 7.1 the missing-Tax and seller-state warnings show in the admin notices bell instead of a banner on every page. Older cores keep the banner.

## 0.2.0

### New
- Buyer GSTIN at checkout: checked for format, state code and check digit, then printed on the invoice.
- Invoice shows the place of supply as `07-Delhi`, "Reverse charge: No" and the state code of the buyer's GSTIN.
- Filter `tax_gst_reverse_charge` prints "Reverse charge: Yes"; amounts are unchanged.

## 0.1.0

### New
- GST split for the Tax plugin: CGST and SGST halves for a buyer in the seller's state, IGST for another state.
- Indian state names matched by GST state code, including old and short spellings.
- Invoice shows the number as GSTIN, tax codes as HSN, the buyer's state code and the kind of supply.
