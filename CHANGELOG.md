# Changelog

## 0.2.1

- Phone menu: arrows now appear only next to items whose sub menu is shown (stray arrows under Post Production, Colour Grading … removed).
- Phone hero: the black band under the header is gone. The source video had cinema bars baked into the frame; the hero video and banner frames are now cropped without them. Hero buttons are equal width.
- Phone text sizes reduced (intro, section headings, labels).
- Client logos: two neat rows of three on phones (moving strip stays on larger screens); logos trimmed so they appear larger.
- Film Restoration reel shows a real frame as its preview (the original preview image was all black).
- New: **Appearance → Black Lab Pages** updates pages created by an earlier version to the new design. Pages nobody edited are ticked; edited pages are left alone unless you tick them, and WordPress keeps the previous version as a revision.

## 0.2.0

- **Every page is now a normal, editable WordPress page.** "Complete Black Lab setup" creates Home (set as the front page), Services, Post Production, Colour Grading, Film Restoration, Digital Lab, Projects, News, Contact, Privacy Policy, Terms & Conditions and FAQ at their original URLs. Text, images, buttons, videos and galleries are ordinary blocks in the block editor. Existing pages are never overwritten and a deleted page is not re-created.
- WordPress's sample "Privacy Policy" draft ("Suggested text: …") is replaced by the site's own Privacy Policy page; logged-in admins no longer see the sample. The untouched "Hello world!" post and "Sample Page" are moved to the trash.
- The menu is a normal WordPress menu (Appearance → Menus), created with the original items and the Services dropdown.
- Videos are added to the Media Library too (images were already).
- New design with the same images, videos, logo and black/gold brand: full-screen video hero with the headline revealed line by line, header over the hero that turns solid on scroll, Cormorant Garamond headings with Manrope text, picture banner on every page, service tiles, project posters, moving client logo strip, contact band and a new footer (services, links, contact details, mailing list).
- Spelling corrected: "PARNTERNS" → "partners", "Louisianna" → "Louisiana", "Jackon" → "Jackson".

## 0.1.0

First release of the Black Lab theme, built from the archived Wix site (blacklabdi.com) and its live supplement.

- All 11 original pages at their original URLs (no `/blacklab` prefix): `/` , `/services`, `/post-production`, `/colour-grading`, `/copy-of-post-production` (Film Restoration), `/copy-of-post-production-1` (Digital Lab), `/projects`, `/blog` (News), `/contact`, `/privacy-policy`, `/terms-conditions`, `/about-7` (FAQ). Links are never renamed. The duplicate Wix homepage `/copy-of-home-1` and the temporary `/blacklab/...` prefix redirect (301) to their real address.
- Original look: white header with boxed gold-bordered menu and Services dropdown, black/white/gold, Anton headings and Jost text (bundled; no third-party font requests).
- Home: looping hero video, original copy, three service cards, client logo strip (pauses on hover), Get In Touch form.
- Services: ten photo tiles (VFX still links to blackvfx.co as on the old site), celluloid services.
- Colour Grading: four before/after sliders using the original graded stills. Film Restoration: original Santa's Chair reel, before/after clips and four graded stills with lightbox. Post Production: editing reel. Digital Lab: icon row and the original text. Projects: all eight titles. Contact: original details, form and six location maps.
- Forms: Contact (First Name, Last Name, Email*, Message, "Send") and footer mailing list ("Subscribe Now"). Saved under **Enquiries** in wp-admin and emailed to info@blacklabdi.com (change under Settings → General). Honeypot, minimum-time and rate-limit protection; no third-party service.
- One-click "Complete Black Lab setup": pretty permalinks, site title "BlackLAB", and Media Library import of all images with descriptive file names and alt text (safe to re-run).
- Original page titles preserved; footer year is generated at runtime.
- In-WordPress updates from GitHub releases (Appearance → Black Lab Updates).
