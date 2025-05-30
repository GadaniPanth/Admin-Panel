import { Component, OnInit } from "@angular/core";
import { AdminService } from "../_services/admin.service";
import { ActivatedRoute, NavigationEnd, Router } from "@angular/router";
import { Title } from "@angular/platform-browser";
import { filter, map, mergeMap } from "rxjs/operators";
import { ToastrService } from "ngx-toastr";

@Component({
  selector: "app-master",
  templateUrl: "./master.component.html",
  styleUrls: ["./master.component.css"],
})
export class MasterComponent implements OnInit {
  constructor(
    public adminService: AdminService,
    private router: Router,
    private route: ActivatedRoute,
    private activatedRoute: ActivatedRoute,
    private titleService: Title,
    private toastr: ToastrService
  ) { }

  // activeTabName: string = "";
  // inquiryCount: number = 0;

  ngOnInit() {
    this.router.events
      .pipe(filter((event) => event instanceof NavigationEnd))
      .subscribe(() => {
        this.updateActiveMenu();
      });

    this.updateActiveMenu(); // Run initially to set active menu
  }

  updateActiveMenu() {
    let route = this.activatedRoute.firstChild;
    while (route.firstChild) {
      route = route.firstChild; // Navigate to the deepest child
    }

    if (route.snapshot.data) {
      this.titleService.setTitle(`${route.snapshot.data.title} | Admin Panel`);
      this.activeTab = route.snapshot.data["tabName"] || "";
    }
  }

  // changeActiveTab(name: string) {
  //   this.activeTabName = name;
  // }

  hideSideMenuF: boolean = false;
  hideSideMenu() {
    this.hideSideMenuF = !this.hideSideMenuF;
  }

  logout() {
    setTimeout(() => {
      this.router.navigate(["/login"]);
      this.adminService.removeTokenData();
      this.toastr.success('Log out Successfully');
    }, 500);
  }

  activeTab = "home";

  setTab(tab: string) {
    this.activeTab = tab;
  }
}
