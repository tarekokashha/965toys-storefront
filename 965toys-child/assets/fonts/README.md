# Fonts

Two open-source typefaces, self-hosted so the store makes no request to a font CDN:

| Font | Used for | Licence |
|---|---|---|
| [Baloo Bhaijaan 2](https://fonts.google.com/specimen/Baloo+Bhaijaan+2) | Display and headings | SIL Open Font License 1.1, see `OFL-BalooBhaijaan2.txt` |
| [Rubik](https://fonts.google.com/specimen/Rubik) | Body and interface | SIL Open Font License 1.1, see `OFL-Rubik.txt` |

The WOFF2 files are the Google Fonts subsets (Arabic, Latin, Latin extended). They are loaded with `unicode-range`, so a page only downloads the subsets it actually uses.
