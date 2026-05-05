export default function Footer() {
  return (
    <footer className="mt-auto border-t border-gray-200 bg-white px-6 py-4 flex items-center justify-between text-xs text-gray-400">
      <p>© {new Date().getFullYear()} OmniSpace 3D Events Ltd. All rights reserved.</p>
      <div className="flex items-center gap-4">
        <span>OmniShop v1.0</span>
        <a href="mailto:support@omnispace3d.com" className="hover:text-teal-600 transition-colors">
          Support
        </a>
      </div>
    </footer>
  );
}
